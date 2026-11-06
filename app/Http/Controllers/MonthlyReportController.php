<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->build($request);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        $data['period'] = 'monthly';

        return view('reports.index', $data);
    }

    public function print(Request $request)
    {
        return view('reports.monthly-print', $this->build($request));
    }

    /**
     * @return array<string, mixed>
     */
    protected function build(Request $request): array
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        try {
            $start = Carbon::parse($month.'-01')->startOfMonth();
        } catch (\Throwable) {
            $start = now()->startOfMonth();
            $month = $start->format('Y-m');
        }

        $end = $start->copy()->endOfMonth();

        $base = Transaction::query()
            ->whereBetween('transaction_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereIn('status', ['success', 'reversed']);

        $totals = (clone $base)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as deposit,
                COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) as withdrawal,
                COUNT(*) as total
            ")
            ->first();

        $perTeller = (clone $base)
            ->selectRaw("
                teller_id,
                COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as deposit,
                COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) as withdrawal,
                COUNT(*) as total
            ")
            ->groupBy('teller_id')
            ->orderByDesc('total')
            ->get();

        $tellers = User::query()
            ->whereIn('id', $perTeller->pluck('teller_id'))
            ->get()
            ->keyBy('id');

        $reports = DailyReport::with('teller')
            ->whereBetween('report_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('report_date')
            ->get();

        $reportSummary = [
            'total' => $reports->count(),
            'approved' => $reports->where('status', 'approved')->count(),
            'submitted' => $reports->where('status', 'submitted')->count(),
            'rejected' => $reports->where('status', 'rejected')->count(),
        ];

        return [
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'totals' => [
                'deposit' => (int) ($totals->deposit ?? 0),
                'withdrawal' => (int) ($totals->withdrawal ?? 0),
                'total' => (int) ($totals->total ?? 0),
            ],
            'perTeller' => $perTeller,
            'tellers' => $tellers,
            'reports' => $reports,
            'reportSummary' => $reportSummary,
        ];
    }
}
