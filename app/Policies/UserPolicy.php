<?php

namespace App\Policies;

class UserPolicy
{
    public function viewAny($user): bool
    {
        return $user?->role === 'admin' || $user?->role === 'supervisor';
    }

    public function view($user, $target = null): bool
    {
        return $user?->role === 'admin' || $user?->role === 'supervisor';
    }

    public function create($user): bool
    {
        return $user?->role === 'admin';
    }

    public function update($user, $target = null): bool
    {
        return $user?->role === 'admin';
    }

    public function delete($user, $target = null): bool
    {
        return $user?->role === 'admin';
    }
}
