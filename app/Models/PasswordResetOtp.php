<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $account_type
 * @property int|null $account_id
 * @property string $email
 * @property string $otp_hash
 * @property int $attempts
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $consumed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class PasswordResetOtp extends Model
{
    public const TYPE_STAFF = 'staff';

    public const TYPE_NASABAH = 'nasabah';

    protected $table = 'password_reset_otps';

    protected $fillable = [
        'account_type',
        'account_id',
        'email',
        'otp_hash',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired();
    }
}
