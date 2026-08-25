<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $transaction_id
 * @property int|null $cash_transfer_id
 * @property string $journal_number
 * @property string $account_code
 * @property string $entry_type
 * @property int $amount
 * @property int $debit_amount
 * @property int $credit_amount
 * @property string|null $description
 * @property string $status
 * @property CarbonInterface $journal_at
 * @property CarbonInterface|null $created_at
 */
class JurnalAkuntansi extends Model
{
    protected $table = 'jurnal_akuntansi';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'cash_transfer_id',
        'journal_number',
        'account_code',
        'entry_type',
        'amount',
        'debit_amount',
        'credit_amount',
        'description',
        'status',
        'journal_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'debit_amount' => 'integer',
        'credit_amount' => 'integer',
        'journal_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /** @return BelongsTo<CashTransfer, $this> */
    public function cashTransfer(): BelongsTo
    {
        return $this->belongsTo(CashTransfer::class, 'cash_transfer_id');
    }
}
