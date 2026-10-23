<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreAccountClosureRequest;
use App\Models\Account;
use App\Services\AccountClosureService;
use App\Services\AuditLogService;

class AccountClosureController extends Controller
{
    public function __construct(
        protected AccountClosureService $closures,
    ) {}

    public function create(Account $account)
    {
        $this->authorize('view', $account);

        $account->load('saver');

        if ($account->status !== 'active') {
            return redirect()->route('teller.members.show', $account)
                ->with('error', 'Rekening ini sudah tidak aktif, jadi tidak bisa diajukan penutupan.');
        }

        if ($account->hasPendingClosure()) {
            return redirect()->route('teller.members.show', $account)
                ->with('error', 'Rekening ini sudah punya permintaan penutupan yang menunggu keputusan Administrator di halaman Kelulusan.');
        }

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('teller.account-closure-create', compact('account'));
    }

    public function store(StoreAccountClosureRequest $request, Account $account)
    {
        $this->authorize('view', $account);

        try {
            $closure = $this->closures->request(
                $account,
                $request->user(),
                $request->validated('pin'),
                $request->validated('notes'),
            );
        } catch (TransactionValidationException $e) {
            AuditLogService::record('account_closure_rejected', 'account', $account->id, [
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

        $message = $closure->payout_amount > 0
            ? 'Saldo Rp '.number_format($closure->payout_amount, 0, ',', '.').' sudah dicairkan. Permintaan penutupan menunggu keputusan Administrator di halaman Kelulusan.'
            : 'Permintaan penutupan rekening dibuat. Menunggu keputusan Administrator di halaman Kelulusan.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $closure,
            ], 201);
        }

        return redirect()->route('teller.members.show', $account)->with('success', $message);
    }
}
