<?php

namespace App\Policies;

class SystemConfigPolicy
{
    public function viewAny($user): bool
    {
        return $user?->role === 'admin';
    }

    public function view($user, $config = null): bool
    {
        return $user?->role === 'admin';
    }

    public function create($user): bool
    {
        return false;
    }

    public function update($user, $config = null): bool
    {
        return $user?->role === 'admin';
    }

    public function delete($user, $config = null): bool
    {
        return false;
    }
}
