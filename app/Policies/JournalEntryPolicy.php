<?php

namespace App\Policies;

use App\Models\JurnalAkuntansi;

class JournalEntryPolicy
{
    public function viewAny($user): bool
    {
        return $user?->role === 'admin' || $user?->role === 'supervisor';
    }

    public function view($user, ?JurnalAkuntansi $entry = null): bool
    {
        return $user?->role === 'admin' || $user?->role === 'supervisor';
    }

    public function create($user): bool
    {
        return false;
    }

    public function update($user, ?JurnalAkuntansi $entry = null): bool
    {
        return false;
    }

    public function delete($user, ?JurnalAkuntansi $entry = null): bool
    {
        return false;
    }
}
