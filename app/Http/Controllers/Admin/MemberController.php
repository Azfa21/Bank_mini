<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberImportRequest;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
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
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($query, $search) {
                $search = trim($search);

                if ($search !== '') {
                    $query->where(function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('graduation_year', 'like', "%{$search}%");
                    });
                }
            })
            ->orderBy('full_name')
            ->paginate(15);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $members,
            ]);
        }

        return view('admin.members.index', compact('members'));
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
            'graduation_year' => $request->graduation_year,
        ], $request->user()->id);

        if ($request->filled('status')) {
            $member->update(['status' => $request->status]);
        }

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
        $data = $request->only(['nis', 'full_name', 'graduation_year', 'status']);

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
            return response()->json([
                'success' => true,
                'message' => "{$summary['imported']} anggota berhasil diimpor.",
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
            fputcsv($handle, ['nis', 'nama', 'angkatan_keluar']);
            fputcsv($handle, ['20260001', 'Budi Santoso', '2026']);
            fputcsv($handle, ['20260002', 'Citra Lestari', '2026']);

            fclose($handle);
        }, 'template-import-anggota.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
