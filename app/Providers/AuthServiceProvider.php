<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\DailyReportPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\SystemConfigPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Account::class => AccountPolicy::class,
        Transaction::class => TransactionPolicy::class,
        DailyReport::class => DailyReportPolicy::class,
        JurnalAkuntansi::class => JournalEntryPolicy::class,
        SystemConfig::class => SystemConfigPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
