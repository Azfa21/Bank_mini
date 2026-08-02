<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyReport extends Model
{
    protected $table = 'daily_reports';

    public $timestamps = false;

    protected $fillable = [
        'report_number',
        'teller_id',
        'report_date',
        'opening_balance',
        'total_deposit',
        'total_withdrawal',
        'closing_balance_system',
        'closing_balance_physical',
        'cash_difference',
        'status',
        'approved_by',
        'approved_at',
        'submitted_at',
        'approval_notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'opening_balance' => 'integer',
        'total_deposit' => 'integer',
        'total_withdrawal' => 'integer',
        'closing_balance_system' => 'integer',
        'closing_balance_physical' => 'integer',
        'cash_difference' => 'integer',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cashTransfer(): HasOne
    {
        return $this->hasOne(CashTransfer::class, 'report_id');
    }

    public function transactions()
    {
        return Transaction::query()
            ->where('teller_id', $this->teller_id)
            ->whereDate('transaction_at', $this->report_date)
            ->where('status', 'success')
            ->orderBy('transaction_at')
            ->orderBy('id');
    }
}
