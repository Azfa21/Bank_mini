<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $nis
 * @property string $full_name
 * @property string|null $graduation_year
 * @property string $status
 * @property int|null $created_by
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Member extends Model
{
    public const STATUS_ACCOUNT_ACTIVE = 'account_active';

    public const STATUS_ACCOUNT_INACTIVE = 'account_inactive';

    protected $table = 'members';

    protected $primaryKey = 'nis';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nis',
        'full_name',
        'graduation_year',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isAccountActive(): bool
    {
        return $this->status === self::STATUS_ACCOUNT_ACTIVE;
    }

    /** @return HasOne<Saver, $this> */
    public function saver(): HasOne
    {
        return $this->hasOne(Saver::class, 'member_nis', 'nis');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
