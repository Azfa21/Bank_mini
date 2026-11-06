<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MonthlyReportController;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(
        Request $request,
        DailyReportController $dailyReports,
        MonthlyReportController $monthlyReports,
    ) {
        if ($request->query('period') === 'monthly') {
            return $monthlyReports->index($request);
        }

        return $dailyReports->index();
    }
}
