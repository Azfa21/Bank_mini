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
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'pin_hash' => 'hashed',
        'created_at' => 'datetime',
    ];

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
