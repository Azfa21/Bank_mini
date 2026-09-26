<?php

namespace App\Support;

use App\Models\Transaction;

class MobileTransactionPresenter
{
    /**
     * @param  iterable<Transaction>  $transactions
     * @return list<array<string, mixed>>
     */
    public static function collection(iterable $transactions, int $accountId): array
    {
        $result = [];

        foreach ($transactions as $transaction) {
            $result[] = self::one($transaction, $accountId);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function one(Transaction $transaction, int $accountId): array
    {
        $isCredit = $transaction->isCreditFor($accountId);

        return [
            'id' => $transaction->id,
            'receipt_number' => $transaction->receipt_number,
            'slip_number' => $transaction->slip_number,
            'type' => $transaction->type,
            'type_label' => $transaction->typeLabel(),
            'direction' => $isCredit ? 'in' : 'out',
            'amount' => (int) $transaction->amount,
            'admin_fee' => (int) $transaction->admin_fee,
            'balance_before' => (int) $transaction->balance_before,
            'balance_after' => (int) $transaction->balance_after,
            'status' => $transaction->status,
            'is_reversal' => $transaction->isReversal(),
            'reversal_reason' => $transaction->reversal_reason,
            'notes' => $transaction->notes,
            'counterpart' => self::counterpart($transaction, $accountId),
            'transaction_at' => optional($transaction->transaction_at)->toIso8601String(),
        ];
    }

    /**
     * @return array{account_number: string, name: string|null}|null
     */
    protected static function counterpart(Transaction $transaction, int $accountId): ?array
    {
        if ($transaction->type !== 'transfer') {
            return null;
        }

        $other = $transaction->account_id === $accountId
            ? $transaction->recipientAccount
            : $transaction->account;

        if ($other === null) {
            return null;
        }

        return [
            'account_number' => $other->account_number,
            'name' => $other->saver?->full_name,
        ];
    }
}
