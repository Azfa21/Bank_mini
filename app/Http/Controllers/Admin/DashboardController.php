<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $topDepositors = $this->analytics->topDepositors(5);
        $tellerPerformance = $this->analytics->tellerPerformance(5);

        return view('admin.dashboard', compact(
            'summary',
            'trend',
            'topDepositors',
            'tellerPerformance'
        ));
    }
}
