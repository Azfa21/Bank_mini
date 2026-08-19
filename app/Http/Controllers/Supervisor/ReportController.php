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

class ReportController extends Controller
{
    public function __construct(
        protected DailyReportService $reports,
    ) {}

    public function index()
    {
        $reports = DailyReport::query()
            ->with(['teller'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('report_date'), fn ($q, $date) => $q->where('report_date', $date))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(15);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);
        }

        return view('supervisor.reports.index', compact('reports'));
    }

    public function show(DailyReport $report)
    {
        $report->load(['teller', 'approver', 'cashTransfer.supervisor']);

        $transactions = $report->transactions()->get();

        $auditLogs = AuditLog::where('entity_name', 'daily_report')
            ->where('entity_id', $report->id)
            ->latest('id')
            ->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'transactions' => $transactions,
                    'audit_logs' => $auditLogs,
                ],
            ]);
        }

        return view('supervisor.reports.show', compact('report', 'transactions', 'auditLogs'));
    }

    public function approve(ApproveReportRequest $request, DailyReport $report)
    {
        try {
            $this->reports->approve($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            AuditLogService::record('daily_report_approval_rejected', 'daily_report', $report->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil disetujui dan kas dipindahkan ke brankas utama.',
                'data' => $report->fresh(['teller', 'approver', 'cashTransfer.supervisor']),
            ]);
        }

        return redirect()->route('supervisor.reports.show', $report)
            ->with('success', 'Laporan berhasil disetujui dan kas dipindahkan ke brankas utama.');
    }

    public function reject(RejectReportRequest $request, DailyReport $report)
    {
        try {
            $this->reports->reject($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan ditolak. Teller dapat memperbaiki dan mengirim ulang.',
                'data' => $report->fresh(['teller', 'approver']),
            ]);
        }

        return redirect()->route('supervisor.reports.show', $report)
            ->with('success', 'Laporan ditolak. Teller dapat memperbaiki dan mengirim ulang.');
    }
}
