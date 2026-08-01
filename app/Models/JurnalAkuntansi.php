<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalAkuntansi extends Model
{
    protected $table = 'jurnal_akuntansi';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account_code',
        'entry_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
