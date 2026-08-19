<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'student_number',
        'full_name',
        'class_name',
        'phone_number',
        'pin_hash',
        'qr_code_value',
        'created_by',
        'status',
        'failed_pin_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'pin_hash' => 'hashed',
        'failed_pin_attempts' => 'integer',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isPinLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function getAuthPassword(): string
    {
        return $this->pin_hash;
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
