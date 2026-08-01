<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    protected $table = 'daily_reports';

    public $timestamps = false;

    protected $fillable = [
        'teller_id',
        'report_date',
        'opening_balance',
        'total_deposit',
        'total_withdrawal',
        'closing_balance_system',
        'closing_balance_physical',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'opening_balance' => 'integer',
        'total_deposit' => 'integer',
        'total_withdrawal' => 'integer',
        'closing_balance_system' => 'integer',
        'closing_balance_physical' => 'integer',
        'approved_at' => 'datetime',
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
}
