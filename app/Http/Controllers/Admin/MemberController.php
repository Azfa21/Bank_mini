<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberImportRequest;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Models\Saver;
use App\Services\MemberImportService;
use App\Services\MemberRegistrationService;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct(
        protected MemberRegistrationService $registrations,
        protected MemberImportService $imports,
    ) {}

    public function index()
    {
        $members = Member::query()
            // Anggota yang sudah jadi penabung dikelola di halaman Penabung. Penabung yang
            // pernah dihapus (soft delete) ikut dikecualikan: sistem tetap melarang anggota
            // itu diaktivasi ulang, jadi menampilkannya di sini hanya menghasilkan baris buntu.
            ->whereNotIn('nis', Saver::withTrashed()->whereNotNull('member_nis')->select('member_nis'))
            // Anggota yang sudah lulus diarsipkan di halaman Kelulusan, bukan di daftar kerja harian.
            ->whereNull('graduated_at')
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('class_name', 'like', "%{$search}%")
                            ->orWhere('graduation_year', 'like', "%{$search}%");
                    });
                }
            })
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $members,
            ]);
        }

        return view('admin.members.index', [
            'members' => $members,
            // Dipakai empty state untuk membedakan "belum ada anggota" dari
            // "semua anggota sudah jadi penabung".
            'hasMembers' => $members->isNotEmpty() || Member::query()->exists(),
        ]);
    }

    public function create(): View
    {
        return view('admin.members.create');
    }

    public function store(StoreMemberRequest $request)
    {
        $member = $this->registrations->register([
            'nis' => $request->nis,
            'full_name' => $request->full_name,
            'class_name' => $request->class_name,
            'graduation_year' => $request->graduation_year,
        ], $request->user()->id);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Anggota berhasil ditambahkan.',
                'data' => $member,
            ], 201);
        }

        return redirect()->route('admin.members.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', compact('member'));
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $data = $request->only(['nis', 'full_name', 'class_name', 'graduation_year']);

        if (array_key_exists('nis', $data) && $data['nis'] !== $member->nis && $member->saver()->withTrashed()->exists()) {
            return back()->with('error', 'NIS tidak dapat diubah karena anggota sudah memiliki rekening penabung.');
        }

        $member->update($data);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Data anggota berhasil diperbarui.',
                'data' => $member->fresh(),
            ]);
        }

        return redirect()->route('admin.members.index')->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        if ($member->saver()->withTrashed()->exists()) {
            if ($this->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anggota tidak dapat dihapus karena memiliki histori rekening penabung.',
                ], 422);
            }

            return redirect()->route('admin.members.index')
                ->with('error', 'Anggota tidak dapat dihapus karena memiliki histori rekening penabung.');
        }

        $name = $member->full_name;
        $member->delete();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Anggota {$name} berhasil dihapus.",
            ]);
        }

        return redirect()->route('admin.members.index')->with('success', "Anggota {$name} berhasil dihapus.");
    }

    public function importForm(): View
    {
        return view('admin.members.import');
    }

    public function import(MemberImportRequest $request)
    {
        try {
            $summary = $this->imports->importFromCsv(
                $request->file('file')->getRealPath(),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($this->wantsJson($request)) {
            $message = "{$summary['imported']} anggota berhasil diimpor.";

            if ($summary['updated'] > 0) {
                $message .= " Kelas {$summary['updated']} anggota diperbarui.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $summary,
            ]);
        }

        return redirect()
            ->route('admin.members.import.form')
            ->with('import_summary', $summary);
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['nis', 'nama', 'angkatan_keluar', 'kelas']);
            fputcsv($handle, ['20260001', 'Budi Santoso', '2029', 'X RPL 1']);
            fputcsv($handle, ['20260002', 'Citra Lestari', '2029', 'X TKJ 2']);

            fclose($handle);
        }, 'template-import-anggota.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
