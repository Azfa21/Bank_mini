<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReverseTransactionRequest;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\ReversalService;

class CorrectionController extends Controller
{
    public function __construct(
        protected ReversalService $reversals,
    ) {}

    public function store(ReverseTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('reverse', $transaction);

        try {
            $reversal = $this->reversals->reverse(
                $transaction,
                $request->user(),
                $request->validated('reason'),
            );
        } catch (TransactionValidationException $e) {
            AuditLogService::record('transaction_reversal_rejected', 'transaction', $transaction->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

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
                'message' => 'Transaksi berhasil dikoreksi dengan jurnal pembalik.',
                'data' => $reversal,
            ], 201);
        }

        return back()->with('success', 'Transaksi '.$transaction->receipt_number.' berhasil dikoreksi dengan jurnal pembalik.');
    }
}
