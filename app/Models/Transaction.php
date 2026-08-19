<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function recipientAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'recipient_account_id');
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'transaction_id');
    }
}
