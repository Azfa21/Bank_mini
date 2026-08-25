<?php

namespace App\Policies;

class DailyReportPolicy
{
    public function viewAny($user): bool
    {
        return in_array($user?->role, ['teller', 'supervisor']);
    }

    public function view($user, $report = null): bool
    {
        if ($user?->role === 'teller') {
            return $report !== null && $report->teller_id === $user->id;
        }

        return $user?->role === 'supervisor';
    }

    public function create($user): bool
    {
        return $user?->role === 'teller';
    }

    public function update($user, $report = null): bool
    {
        return false;
    }

    public function delete($user, $report = null): bool
    {
        return false;
    }

    public function approve($user, $report = null): bool
    {
        return $user?->role === 'supervisor';
    }

    public function reject($user, $report = null): bool
    {
        return $user?->role === 'supervisor';
    }
}
