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
}
