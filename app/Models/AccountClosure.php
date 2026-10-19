<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan penutupan rekening karena kelulusan. Pencairan saldo dilakukan Teller
 * di loket, lalu Administrator menyetujui atau menolaknya.
 *
 * @property int $id
 * @property int $account_id
 * @property int $saver_id
 * @property int $requested_by
 * @property int|null $payout_transaction_id
 * @property int $payout_amount
 * @property string $status
 * @property int|null $decided_by
 * @property CarbonInterface|null $decided_at
 * @property string|null $notes
 * @property string|null $decision_notes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class AccountClosure extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'account_closures';

    protected $fillable = [
        'account_id',
        'saver_id',
        'requested_by',
        'payout_transaction_id',
        'payout_amount',
        'status',
        'decided_by',
        'decided_at',
        'notes',
        'decision_notes',
    ];

    protected $casts = [
        'payout_amount' => 'integer',
        'decided_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Saver, $this> */
    public function saver(): BelongsTo
    {
        return $this->belongsTo(Saver::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function payoutTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payout_transaction_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            default => ucfirst($this->status),
        };
    }
}
