<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreDepositRequest;
use App\Http\Requests\Teller\StoreTransferRequest;
use App\Http\Requests\Teller\StoreWithdrawalRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\TransactionService;
use App\Services\WithdrawalBookingService;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactions,
        protected WithdrawalBookingService $withdrawalBookings,
    ) {}

    public function createDeposit(Account $account)
    {
        $account->load('saver');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('teller.deposit-create', compact('account'));
    }

    public function storeDeposit(StoreDepositRequest $request, Account $account)
    {
        $this->authorize('create', Transaction::class);

        $data = $request->validated();
        $data['amount'] = (int) $data['slip_amount'];

        try {
            $transaction = $this->transactions->deposit($account, $data, $request->user());
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_deposit_rejected', 'account', $account->id, [
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
                'message' => 'Setoran tunai berhasil disimpan.',
                'data' => $transaction->load(['account.saver', 'teller']),
            ], 201);
        }

        return redirect()->route('teller.transactions.receipt', $transaction);
    }

    public function createWithdrawal(Account $account)
    {
        $account->load('saver');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        $withdrawalDaily = $this->withdrawalBookings->dailyStatus($account);

        return view('teller.withdrawal-create', compact('account', 'withdrawalDaily'));
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request, Account $account)
    {
        $this->authorize('create', Transaction::class);

        $data = $request->validated();

        try {
            $transaction = $this->transactions->withdraw($account, $data, $request->input('pin'), $request->user());
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_withdrawal_rejected', 'account', $account->id, [
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
                'message' => 'Penarikan tunai berhasil disimpan.',
                'data' => $transaction->load(['account.saver', 'teller']),
            ], 201);
        }

        return redirect()->route('teller.transactions.receipt', $transaction);
    }

    public function createTransfer(Account $account)
    {
        $account->load('saver');

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $account,
            ]);
        }

        return view('teller.transfer-create', compact('account'));
    }

    public function storeTransfer(StoreTransferRequest $request, Account $account)
    {
        $this->authorize('create', Transaction::class);

        $recipientAccount = Account::where('account_number', $request->input('recipient_account_number'))->firstOrFail();

        try {
            $transaction = $this->transactions->transfer(
                $account,
                $recipientAccount,
                $request->validated(),
                $request->input('pin'),
                $request->user()
            );
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_transfer_rejected', 'account', $account->id, [
                'reason' => $e->getMessage(),
                'recipient_account_number' => $request->input('recipient_account_number'),
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
                'message' => 'Transfer saldo berhasil diproses.',
                'data' => $transaction->load(['account.saver', 'recipientAccount.saver', 'teller']),
            ], 201);
        }

        return redirect()->route('teller.transactions.receipt', $transaction);
    }

    public function receipt(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'account.saver',
            'recipientAccount.saver',
            'teller',
            'journalEntries',
            'reversalOf.account.saver',
            'reversedBy',
        ]);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        }

        return view('teller.receipt', compact('transaction'));
    }
}
