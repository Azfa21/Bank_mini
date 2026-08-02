<?php

namespace App\Http\Controllers\Supervisor;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\ApproveReportRequest;
use App\Http\Requests\Supervisor\RejectReportRequest;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Services\AuditLogService;
use App\Services\DailyReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected DailyReportService $reports,
    ) {}

    public function index(): View
    {
        $reports = DailyReport::query()
            ->with(['teller'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('report_date'), fn ($q, $date) => $q->where('report_date', $date))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('supervisor.reports.index', compact('reports'));
    }

    public function show(DailyReport $report): View
    {
        $report->load(['teller', 'approver', 'cashTransfer.supervisor']);

        $transactions = $report->transactions()->get();

        $auditLogs = AuditLog::where('entity_name', 'daily_report')
            ->where('entity_id', $report->id)
            ->latest('id')
            ->get();

        return view('supervisor.reports.show', compact('report', 'transactions', 'auditLogs'));
    }

    public function approve(ApproveReportRequest $request, DailyReport $report): RedirectResponse
    {
        try {
            $this->reports->approve($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            AuditLogService::record('daily_report_approval_rejected', 'daily_report', $report->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('supervisor.reports.show', $report)
            ->with('success', 'Laporan berhasil disetujui dan kas dipindahkan ke brankas utama.');
    }

    public function reject(RejectReportRequest $request, DailyReport $report): RedirectResponse
    {
        try {
            $this->reports->reject($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('supervisor.reports.show', $report)
            ->with('success', 'Laporan ditolak. Teller dapat memperbaiki dan mengirim ulang.');
    }
}
