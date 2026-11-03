<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $teller_id
 * @property int $amount
 * @property string $request_number
 * @property string $status
 * @property int|null $approved_by
 * @property CarbonInterface|null $approved_at
 * @property string|null $decision_notes
 * @property CarbonInterface|null $created_at
 */
class CashRequest extends Model
{
    protected $table = 'cash_requests';

    public $timestamps = false;

    protected $fillable = [
        'teller_id',
        'amount',
        'request_number',
        'status',
        'approved_by',
        'approved_at',
        'decision_notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<JurnalAkuntansi, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'cash_request_id');
    }

    /**
     * Label status untuk layar staf, mengikuti konvensi DailyReport::statusLabel().
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    }
}
