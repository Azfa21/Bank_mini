<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $saver_id
 * @property string $name
 * @property string $token_hash
 * @property CarbonInterface|null $last_used_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $created_at
 */
class MobileToken extends Model
{
    protected $table = 'mobile_tokens';

    public $timestamps = false;

    protected $fillable = [
        'saver_id',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
        'created_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** @return BelongsTo<Saver, $this> */
    public function saver(): BelongsTo
    {
        return $this->belongsTo(Saver::class, 'saver_id');
    }
}
