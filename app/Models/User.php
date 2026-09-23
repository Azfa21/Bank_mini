<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $full_name
 * @property string $username
 * @property string|null $email
 * @property string $password_hash
 * @property string $role
 * @property string $status
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'full_name',
        'role',
        'status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'teller_id');
    }

    /** @return HasMany<DailyReport, $this> */
    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'teller_id');
    }

    /** @return HasMany<DailyReport, $this> */
    public function approvedReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'approved_by');
    }
}
