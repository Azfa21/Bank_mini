<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $account_number
 * @property int $customer_id
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
        'customer_id',
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

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
