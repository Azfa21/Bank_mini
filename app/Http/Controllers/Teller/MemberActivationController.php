<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreMemberActivationRequest;
use App\Models\Account;
use App\Models\Member;
use App\Services\AuditLogService;
use App\Services\MemberActivationService;

class MemberActivationController extends Controller
{
    public function __construct(
        protected MemberActivationService $activations,
    ) {}

    public function create(Member $member)
    {
        $this->authorize('viewAny', Account::class);

        if ($member->status === Member::STATUS_ACCOUNT_ACTIVE) {
            $account = $member->saver?->account;

            if ($account) {
                return redirect()->route('teller.members.show', $account);
            }
        }

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $member,
            ]);
        }

        return view('teller.member-activate', compact('member'));
    }

    public function store(StoreMemberActivationRequest $request, Member $member)
    {
        $this->authorize('viewAny', Account::class);

        try {
            $result = $this->activations->activate($member, $request->validated(), $request->user());
        } catch (TransactionValidationException $e) {
            AuditLogService::record('member_activation_rejected', 'member', null, [
                'nis' => $member->nis,
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Rekening berhasil diaktifkan.',
                'data' => [
                    'saver' => $result['saver'],
                    'account' => $result['account'],
                    'transaction' => $result['transaction'],
                ],
            ], 201);
        }

        return redirect()->route('teller.transactions.receipt', $result['transaction']);
    }
}
