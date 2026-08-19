<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function cashTransfer(): BelongsTo
    {
        return $this->belongsTo(CashTransfer::class, 'cash_transfer_id');
    }
}
