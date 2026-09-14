<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $config_key
 * @property string $config_value
 * @property int|null $updated_by
 * @property CarbonInterface|null $updated_at
 */
class SystemConfig extends Model
{
    protected $table = 'system_configs';

    protected $primaryKey = 'config_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'config_key',
        'config_value',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        $value = static::query()->whereKey($key)->value('config_value');

        return $value ?? $default;
    }

    public static function integer(string $key, int $default = 0): int
    {
        $value = static::value($key);

        return $value === null || $value === '' ? $default : (int) $value;
    }
}
