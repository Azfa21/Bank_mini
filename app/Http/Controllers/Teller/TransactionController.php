<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreDepositRequest;
use App\Http\Requests\Teller\StoreWithdrawalRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactions,
    ) {}

    public function createDeposit(Account $account): View
    {
        $account->load('customer');

        return view('teller.deposit-create', compact('account'));
    }

    public function storeDeposit(StoreDepositRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();
        $data['amount'] = (int) $data['slip_amount'];

        try {
            $transaction = $this->transactions->deposit($account, $data, $request->user());
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_deposit_rejected', 'account', $account->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('teller.transactions.receipt', $transaction);
    }

    public function createWithdrawal(Account $account): View
    {
        $account->load('customer');

        return view('teller.withdrawal-create', compact('account'));
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();

        try {
            $transaction = $this->transactions->withdraw($account, $data, $request->input('pin'), $request->user());
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_withdrawal_rejected', 'account', $account->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('teller.transactions.receipt', $transaction);
    }

    public function receipt(Transaction $transaction): View
    {
        $transaction->load(['account.customer', 'teller']);

        return view('teller.receipt', compact('transaction'));
    }
}
