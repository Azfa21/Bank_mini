<?php

namespace App\Policies;

class TransactionPolicy
{
    public function viewAny($user): bool
    {
        return $user?->role === 'supervisor';
    }

    public function view($user, $transaction = null): bool
    {
        return $user?->role === 'supervisor';
    }

    public function create($user): bool
    {
        return $user?->role === 'teller';
    }

    public function update($user, $transaction = null): bool
    {
        return false;
    }

    public function delete($user, $transaction = null): bool
    {
        return false;
    }
}
