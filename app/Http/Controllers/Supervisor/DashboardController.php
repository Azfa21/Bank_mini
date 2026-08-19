<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics,
    ) {}

    public function index(Request $request): View
    {
        $summary = $this->analytics->summary();
        $trend = $this->analytics->trend(7);

        $waiting = DailyReport::where('status', 'submitted')->count();
        $approved = DailyReport::where('status', 'approved')->count();
        $todayTransactions = Transaction::whereDate('transaction_at', now()->toDateString())
            ->where('status', 'success')
            ->count();
        $recentLogs = AuditLog::latest('id')->limit(5)->get();

        return view('supervisor.dashboard', compact(
            'summary',
            'trend',
            'waiting',
            'approved',
            'todayTransactions',
            'recentLogs'
        ));
    }
}
