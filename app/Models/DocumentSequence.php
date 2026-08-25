<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property int $last_number
 */
class DocumentSequence extends Model
{
    protected $table = 'document_sequences';

    protected $fillable = [
        'key',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];
}
