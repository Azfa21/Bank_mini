<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GraduateBatchRequest;
use App\Http\Requests\Admin\GraduateMemberRequest;
use App\Models\Member;
use App\Services\GraduationService;
use Illuminate\Support\Collection;

class GraduationController extends Controller
{
    public function __construct(
        protected GraduationService $graduations,
    ) {}

    public function index()
    {
        $years = Member::query()
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year');

        $year = request('year');

        $rows = $year !== null && $year !== ''
            ? $this->graduations->membersFor($year)->map(fn (Member $member) => [
                'member' => $member,
                'saver' => $member->saver,
                'account' => $member->saver?->account,
                'state' => $this->graduations->stateFor($member),
            ])->values()
            : new Collection;

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'years' => $years,
                    'year' => $year,
                    'members' => $rows,
                ],
            ]);
        }

        return view('admin.graduations.index', compact('years', 'year', 'rows'));
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

        return redirect()->route('admin.graduations.index', ['year' => $year])->with('success', $message);
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
                ->route('admin.graduations.index', ['year' => $member->graduation_year])
                ->with('error', $message);
        }

        $message = "Anggota {$member->full_name} diarsipkan sebagai alumni.";

        if ($this->wantsJson($request)) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $member]);
        }

        return redirect()
            ->route('admin.graduations.index', ['year' => $member->graduation_year])
            ->with('success', $message);
    }
}
