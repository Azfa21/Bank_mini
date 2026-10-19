<?php

namespace App\Policies;

class AccountClosurePolicy
{
    public function viewAny($user): bool
    {
        return $user?->role === 'admin';
    }

    public function view($user, $closure = null): bool
    {
        return $user?->role === 'admin';
    }

    public function create($user): bool
    {
        return $user?->role === 'teller';
    }

    public function approve($user, $closure = null): bool
    {
        return $user?->role === 'admin';
    }

    public function reject($user, $closure = null): bool
    {
        return $user?->role === 'admin';
    }
}
