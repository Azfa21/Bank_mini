<?php

namespace App\Policies;

class AccountPolicy
{
    public function viewAny($user): bool
    {
        return in_array($user?->role, ['admin', 'teller']);
    }

    public function view($user, $account = null): bool
    {
        return in_array($user?->role, ['admin', 'teller']);
    }

    public function create($user): bool
    {
        return $user?->role === 'admin';
    }

    public function update($user, $account = null): bool
    {
        return $user?->role === 'admin';
    }

    public function delete($user, $account = null): bool
    {
        return false;
    }
}
