<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $report_id
 * @property int $supervisor_id
 * @property int $amount
 * @property string $source_location
 * @property string $destination_location
 * @property string $status
 * @property CarbonInterface|null $transferred_at
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 */
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

    /** @return BelongsTo<DailyReport, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** @return HasMany<JurnalAkuntansi, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'cash_transfer_id');
    }
}
