<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $student_number
 * @property string $full_name
 * @property string|null $class_name
 * @property string|null $phone_number
 * @property string|null $email
 * @property string $pin_hash
 * @property string $qr_code_value
 * @property int|null $created_by
 * @property string $status
 * @property int $failed_pin_attempts
 * @property CarbonInterface|null $locked_until
 * @property CarbonInterface|null $created_at
 */
class Customer extends Authenticatable
{
    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'student_number',
        'full_name',
        'class_name',
        'phone_number',
        'email',
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

    /** @return HasOne<Account, $this> */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
