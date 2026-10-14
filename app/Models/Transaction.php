<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $account_id
 * @property int|null $recipient_account_id
 * @property int $teller_id
 * @property string $type
 * @property int $amount
 * @property int|null $slip_amount
 * @property int|null $physical_cash_amount
 * @property array<string, int>|null $denomination_breakdown
 * @property int $balance_before
 * @property int $balance_after
 * @property string $receipt_number
 * @property string|null $slip_number
 * @property string|null $notes
 * @property int|null $reversal_of_transaction_id
 * @property CarbonInterface|null $reversed_at
 * @property int|null $reversed_by
 * @property string|null $reversal_reason
 * @property string $status
 * @property CarbonInterface $transaction_at
 * @property CarbonInterface|null $created_at
 */
class Transaction extends Model
{
    protected $table = 'transactions';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'recipient_account_id',
        'teller_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'receipt_number',
        'slip_number',
        'slip_amount',
        'physical_cash_amount',
        'denomination_breakdown',
        'notes',
        'reversal_of_transaction_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'status',
        'transaction_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'slip_amount' => 'integer',
        'physical_cash_amount' => 'integer',
        'denomination_breakdown' => 'array',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'reversed_at' => 'datetime',
        'transaction_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function recipientAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'recipient_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    /** @return HasMany<JurnalAkuntansi, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'transaction_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reversal_of_transaction_id');
    }

    /** @return HasMany<Transaction, $this> */
    public function reversals(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reversal_of_transaction_id');
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed' || $this->reversed_at !== null;
    }

    public function isReversal(): bool
    {
        return $this->type === 'reversal';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'deposit' => 'Setoran',
            'withdrawal' => 'Penarikan',
            'transfer' => 'Transfer',
            'reversal' => 'Koreksi',
            default => ucfirst($this->type),
        };
    }

    /**
     * Menentukan apakah transaksi menambah saldo rekening tertentu.
     */
    public function isCreditFor(int $accountId): bool
    {
        return match ($this->type) {
            'deposit' => $this->account_id === $accountId,
            'withdrawal' => false,
            'transfer' => $this->recipient_account_id === $accountId,
            'reversal' => $this->reversalCreditsAccount($accountId),
            default => false,
        };
    }

    protected function reversalCreditsAccount(int $accountId): bool
    {
        return match ($this->reversalOf?->type) {
            'withdrawal' => true,
            'deposit' => false,
            'transfer' => $this->account_id === $accountId,
            default => false,
        };
    }
}
