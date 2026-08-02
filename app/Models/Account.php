<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
