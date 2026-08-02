<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreClosingRequest;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\DailyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClosingController extends Controller
{
    public function __construct(
        protected DailyReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->query('report_date', now()->toDateString());

        $recap = $this->reports->recap($request->user(), $date);

        $report = DailyReport::where('teller_id', $request->user()->id)
            ->where('report_date', $date)
            ->first();

        $transactions = Transaction::where('teller_id', $request->user()->id)
            ->whereDate('transaction_at', $date)
            ->where('status', 'success')
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        return view('teller.closing', compact('date', 'recap', 'report', 'transactions'));
    }

    public function store(StoreClosingRequest $request): RedirectResponse
    {
        $date = $request->validated('report_date');
        $physicalCash = (int) $request->validated('physical_cash');

        try {
            $report = $this->reports->createReport($request->user(), $date, $physicalCash);
        } catch (TransactionValidationException $e) {
            AuditLogService::record('daily_report_creation_blocked', 'daily_report', null, [
                'report_date' => $date,
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            return back()
                ->with('error', $e->getMessage())
                ->withInput(['report_date' => $date]);
        }

        return redirect()->route('teller.closing.print', $report);
    }

    public function print(DailyReport $report): View
    {
        if ($report->teller_id !== auth()->id()) {
            abort(403);
        }

        $report->load(['teller', 'approver']);

        return view('teller.report-print', compact('report'));
    }
}
