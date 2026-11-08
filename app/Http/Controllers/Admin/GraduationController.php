<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveClosureRequest;
use App\Http\Requests\Admin\GraduateBatchRequest;
use App\Http\Requests\Admin\GraduateMemberRequest;
use App\Http\Requests\Admin\NotGraduateRequest;
use App\Http\Requests\Admin\RejectClosureRequest;
use App\Models\AccountClosure;
use App\Models\Member;
use App\Models\Saver;
use App\Services\AccountClosureService;
use App\Services\AuditLogService;
use App\Services\GraduationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GraduationController extends Controller
{
    public function __construct(
        protected GraduationService $graduations,
        protected AccountClosureService $closures,
    ) {}

    public function index()
    {
        $years = Member::query()
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year');

        $year = request('year');

        // Hitungan untuk tombol unduh arsip; alumni berarti anggota atau
        // penabungnya sudah ditandai lulus.
        $alumniCount = Member::query()
            ->where(fn ($query) => $query
                ->whereNotNull('graduated_at')
                ->orWhereHas('saver', fn ($saver) => $saver->where('status', Saver::STATUS_GRADUATED)))
            ->count();

        $rows = $year !== null && $year !== ''
            ? $this->graduations->membersFor($year)->map(fn (Member $member) => [
                'member' => $member,
                'saver' => $member->saver,
                'account' => $member->saver?->account,
                'state' => $this->graduations->stateFor($member),
                'closure' => $member->saver?->account?->pendingClosure,
            ])->values()
            : new Collection;

        // Penutupan tanpa angkatan tidak akan muncul di daftar tahun mana pun, jadi
        // dipisahkan agar keputusannya tidak menggantung.
        $unassignedClosures = AccountClosure::query()
            ->with(['saver', 'account', 'requester'])
            ->where('status', AccountClosure::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereDoesntHave('saver.member')
                    ->orWhereHas('saver.member', function ($sub) {
                        $sub->whereNull('graduation_year')->orWhere('graduation_year', '');
                    });
            })
            ->orderBy('created_at')
            ->get();

        // Jumlah ini dipakai untuk menandai angkatan yang punya keputusan tertunda,
        // supaya antrean tidak tenggelam di balik filter angkatan.
        $pendingByYear = AccountClosure::query()
            ->where('status', AccountClosure::STATUS_PENDING)
            ->whereHas('saver.member', function ($query) {
                $query->whereNotNull('graduation_year')->where('graduation_year', '!=', '');
            })
            ->with('saver.member')
            ->get()
            ->groupBy(fn (AccountClosure $closure) => (string) $closure->saver?->member?->graduation_year)
            ->map(fn (Collection $group) => $group->count());

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'years' => $years,
                    'year' => $year,
                    'members' => $rows,
                    'unassigned_closures' => $unassignedClosures,
                    'pending_by_year' => $pendingByYear,
                ],
            ]);
        }

        return view('admin.graduations.index', compact('years', 'year', 'rows', 'unassignedClosures', 'pendingByYear', 'alumniCount'));
    }

    /**
     * Unduh arsip CSV alumni. Salinan yang sama selalu ditulis ke
     * storage/app/private/backups/alumni supaya arsip tetap ada di server
     * walaupun berkas unduhan operator hilang. Empat kolom pertama mengikuti
     * templat impor anggota agar berkasnya bisa dipakai ulang bila suatu saat
     * data induk perlu dimuat kembali.
     */
    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        $year = trim((string) $request->query('year', ''));

        $knownYears = Member::query()
            ->whereNotNull('graduation_year')
            ->where('graduation_year', '!=', '')
            ->distinct()
            ->pluck('graduation_year');

        if ($year !== '' && ! $knownYears->contains($year)) {
            return redirect()
                ->route('admin.student-lifecycle.index', ['section' => 'graduations'])
                ->with('error', "Tidak ada angkatan {$year} pada data anggota.");
        }

        $alumni = Member::query()
            ->with(['saver.account'])
            ->when($year !== '', fn ($query) => $query->where('graduation_year', $year))
            ->where(fn ($query) => $query
                ->whereNotNull('graduated_at')
                ->orWhereHas('saver', fn ($saver) => $saver->where('status', Saver::STATUS_GRADUATED)))
            ->when($year === '', fn ($query) => $query->orderBy('graduation_year'))
            ->orderBy('full_name')
            ->get();

        if ($alumni->isEmpty()) {
            $message = $year !== ''
                ? "Belum ada alumni pada angkatan {$year}."
                : 'Belum ada alumni untuk diarsipkan.';

            return redirect()
                ->route('admin.student-lifecycle.index', $year !== '' ? ['section' => 'graduations', 'year' => $year] : ['section' => 'graduations'])
                ->with('error', $message);
        }

        $filename = 'arsip-alumni-'.($year !== '' ? 'angkatan-'.$year : 'semua').'-'.now()->format('Ymd_His').'.csv';

        $disk = Storage::disk('local');
        $disk->makeDirectory('backups/alumni');
        $path = 'backups/alumni/'.$filename;

        $handle = fopen($disk->path($path), 'w');

        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

        fputcsv($handle, ['nis', 'nama', 'angkatan_keluar', 'kelas', 'tanggal_lulus', 'nomor_rekening', 'status_rekening', 'saldo_akhir_rp', 'rekening_ditutup_pada', 'status_penabung']);

        foreach ($alumni as $member) {
            $saver = $member->saver;
            $account = $saver?->account;

            fputcsv($handle, [
                $member->nis,
                $member->full_name,
                $member->graduation_year ?? '',
                $member->class_name ?? $saver->class_name ?? '',
                ($member->graduated_at ?? $saver->graduated_at)?->format('Y-m-d H:i:s') ?? '',
                $account->account_number ?? '',
                $account->status ?? '',
                (string) (int) ($account->balance ?? 0),
                $account !== null && $account->closed_at !== null
                    ? $account->closed_at->format('Y-m-d H:i:s')
                    : '',
                $saver->status ?? '',
            ]);
        }

        fclose($handle);

        AuditLogService::record('alumni_archive_exported', 'member', null, [
            'scope' => $year !== '' ? 'angkatan '.$year : 'semua angkatan',
            'rows' => $alumni->count(),
            'csv_path' => $path,
        ], $request->user()?->id);

        return response()->download($disk->path($path), $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function store(GraduateBatchRequest $request)
    {
        $year = $request->validated('graduation_year');

        $result = $this->graduations->graduateBatch($year, $request->user());

        $message = "Angkatan {$year}: {$result['archived']} siswa diarsipkan.";

        if ($result['pending_counter'] > 0) {
            $message .= ' '.$result['pending_counter'].' penabung masih bersaldo dan perlu diselesaikan di loket Teller.';
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        }

        return redirect()->route('admin.student-lifecycle.index', ['section' => 'graduations', 'year' => $year])->with('success', $message);
    }

    public function graduateMember(GraduateMemberRequest $request, Member $member)
    {
        $archived = $this->graduations->graduateMember($member, $request->user());

        if (! $archived) {
            $message = 'Anggota ini tidak dapat diarsipkan: mungkin sudah alumni atau rekeningnya masih bersaldo. Selesaikan saldo di loket lebih dulu.';

            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()
                ->route('admin.student-lifecycle.index', ['section' => 'graduations', 'year' => $member->graduation_year])
                ->with('error', $message);
        }

        $message = "Anggota {$member->full_name} diarsipkan sebagai alumni.";

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $member]);
        }

        return redirect()
            ->route('admin.student-lifecycle.index', ['section' => 'graduations', 'year' => $member->graduation_year])
            ->with('success', $message);
    }

    public function notGraduate(NotGraduateRequest $request, Member $member)
    {
        $year = $member->graduation_year;

        try {
            $result = $this->graduations->notGraduate(
                $member,
                $request->user(),
                $request->validated('graduation_year'),
                $request->validated('reason'),
            );
        } catch (TransactionValidationException $e) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Anggota {$member->full_name} ditandai tidak lulus. Rekening tetap aktif";

        if ($result['reversed']) {
            $message .= ' dan saldo yang sudah dicairkan dikembalikan.';
        } else {
            $message .= '.';
        }

        $message .= $result['next_year'] !== null
            ? " Siswa masuk angkatan {$result['next_year']}."
            : ' Isi angkatan keluar berikutnya lewat Kelola Anggota.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        }

        return redirect()
            ->route('admin.student-lifecycle.index', $year !== null ? ['section' => 'graduations', 'year' => $year] : ['section' => 'graduations'])
            ->with('success', $message);
    }

    public function approveClosure(ApproveClosureRequest $request, AccountClosure $closure)
    {
        try {
            $this->closures->approve($closure, $request->user(), $request->validated('decision_notes'));
        } catch (TransactionValidationException $e) {
            AuditLogService::record('account_closure_approval_rejected', 'account_closure', $closure->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = 'Penutupan rekening disetujui. Penabung ditandai sebagai alumni.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $closure->fresh(['saver', 'account', 'decider']),
            ]);
        }

        return $this->backToGraduations($closure)->with('success', $message);
    }

    public function rejectClosure(RejectClosureRequest $request, AccountClosure $closure)
    {
        try {
            $this->closures->reject($closure, $request->user(), $request->validated('rejection_reason'));
        } catch (TransactionValidationException $e) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = 'Permintaan penutupan ditolak. Saldo penabung dikembalikan dan rekening tetap aktif.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $closure->fresh(['saver', 'account', 'decider']),
            ]);
        }

        return $this->backToGraduations($closure)->with('success', $message);
    }

    private function backToGraduations(AccountClosure $closure): RedirectResponse
    {
        $year = $closure->saver?->member?->graduation_year;

        return redirect()->route('admin.student-lifecycle.index', $year ? ['section' => 'graduations', 'year' => $year] : ['section' => 'graduations']);
    }
}
