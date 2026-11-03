<?php

namespace App\Policies;

class CashRequestPolicy
{
    public function viewAny($user): bool
    {
        return in_array($user?->role, ['teller', 'admin']);
    }

    public function view($user, $request = null): bool
    {
        if ($user?->role === 'teller') {
            return $request !== null && $request->teller_id === $user->id;
        }

        return $user?->role === 'admin';
    }

    public function create($user): bool
    {
        return $user?->role === 'teller';
    }

    public function approve($user, $request = null): bool
    {
        return $user?->role === 'admin';
    }

    public function reject($user, $request = null): bool
    {
        return $user?->role === 'admin';
    }
}
