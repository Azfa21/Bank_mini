<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $student_number
 * @property string|null $member_nis
 * @property string $full_name
 * @property string|null $class_name
 * @property string|null $phone_number
 * @property string|null $email
 * @property string $pin_hash
 * @property string $qr_code_value
 * @property int|null $created_by
 * @property string $status
 * @property CarbonInterface|null $graduated_at
 * @property int $failed_pin_attempts
 * @property CarbonInterface|null $locked_until
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface|null $created_at
 */
class Saver extends Authenticatable
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_GRADUATED = 'graduated';

    protected $table = 'savers';

    public $timestamps = false;

    protected $fillable = [
        'student_number',
        'member_nis',
        'full_name',
        'class_name',
        'phone_number',
        'email',
        'pin_hash',
        'qr_code_value',
        'created_by',
        'status',
        'graduated_at',
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
        'graduated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isPinLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isGraduated(): bool
    {
        return $this->status === self::STATUS_GRADUATED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_BLOCKED => 'Diblokir',
            self::STATUS_GRADUATED => 'Alumni',
            default => ucfirst($this->status),
        };
    }

    public function getAuthPassword(): string
    {
        return $this->pin_hash;
    }

    /** @return HasOne<Account, $this> */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class, 'saver_id');
    }

    /** @return HasMany<AccountClosure, $this> */
    public function closures(): HasMany
    {
        return $this->hasMany(AccountClosure::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_nis', 'nis');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
