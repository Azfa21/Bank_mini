<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $account_number
 * @property int $saver_id
 * @property int $balance
 * @property int $opening_balance
 * @property int $minimum_balance
 * @property string $academic_year
 * @property string $status
 * @property CarbonInterface|null $opened_at
 * @property CarbonInterface|null $closed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Account extends Model
{
    protected $table = 'accounts';

    protected $fillable = [
        'account_number',
        'saver_id',
        'balance',
        'opening_balance',
        'minimum_balance',
        'academic_year',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'balance' => 'integer',
        'opening_balance' => 'integer',
        'minimum_balance' => 'integer',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getCurrentBalanceAttribute(): int
    {
        return (int) $this->attributes['balance'];
    }

    /** @return BelongsTo<Saver, $this> */
    public function saver(): BelongsTo
    {
        return $this->belongsTo(Saver::class, 'saver_id')->withTrashed();
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<AccountClosure, $this> */
    public function closures(): HasMany
    {
        return $this->hasMany(AccountClosure::class);
    }

    /** @return HasOne<AccountClosure, $this> */
    public function pendingClosure(): HasOne
    {
        return $this->hasOne(AccountClosure::class)->where('status', AccountClosure::STATUS_PENDING);
    }

    public function hasPendingClosure(): bool
    {
        return $this->relationLoaded('pendingClosure')
            ? $this->pendingClosure !== null
            : $this->pendingClosure()->exists();
    }
}
