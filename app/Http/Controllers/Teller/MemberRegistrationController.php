<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreMemberRegistrationRequest;
use App\Models\Account;
use App\Services\MemberRegistrationService;
use Illuminate\View\View;

class MemberRegistrationController extends Controller
{
    public function __construct(
        protected MemberRegistrationService $registrations,
    ) {}

    public function create(): View
    {
        $this->authorize('viewAny', Account::class);

        return view('teller.member-register');
    }

    public function store(StoreMemberRegistrationRequest $request)
    {
        $this->authorize('viewAny', Account::class);

        $member = $this->registrations->register([
            'nis' => $request->validated('nis'),
            'full_name' => $request->validated('full_name'),
            'graduation_year' => $request->validated('graduation_year'),
        ], $request->user()->id);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Anggota berhasil didaftarkan.',
                'data' => $member,
            ], 201);
        }

        return redirect()->route('teller.members.register')
            ->with('success', 'Anggota '.$member->full_name.' berhasil didaftarkan.')
            ->with('registration', [
                'full_name' => $member->full_name,
                'nis' => $member->nis,
                'graduation_year' => $member->graduation_year,
            ]);
    }
}
