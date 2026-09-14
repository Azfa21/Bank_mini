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
        if ($user?->role === 'supervisor') {
            return true;
        }

        return $user?->role === 'teller'
            && $transaction !== null
            && $transaction->teller_id === $user->id;
    }

    public function create($user): bool
    {
        return $user?->role === 'teller';
    }

    public function reverse($user, $transaction = null): bool
    {
        return $user?->role === 'supervisor'
            && $transaction !== null
            && $transaction->status === 'success'
            && $transaction->type !== 'reversal';
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
