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
 * @property int $balance_before
 * @property int $balance_after
 * @property string $receipt_number
 * @property string|null $slip_number
 * @property string|null $notes
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
        'notes',
        'status',
        'transaction_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'slip_amount' => 'integer',
        'physical_cash_amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
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
}
