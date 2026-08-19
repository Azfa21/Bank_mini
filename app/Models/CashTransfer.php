<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashTransfer extends Model
{
    protected $table = 'cash_transfers';

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'supervisor_id',
        'amount',
        'source_location',
        'destination_location',
        'status',
        'transferred_at',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transferred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'cash_transfer_id');
    }
}
