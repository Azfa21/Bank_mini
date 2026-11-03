<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveReportRequest;
use App\Http\Requests\Admin\RejectReportRequest;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Services\AuditLogService;
use App\Services\DailyReportService;

class DailyReportController extends Controller
{
    public function __construct(
        protected DailyReportService $reports,
    ) {}

    public function index()
    {
        $this->authorize('viewAny', DailyReport::class);

        $reports = DailyReport::query()
            ->with(['teller'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('report_date'), fn ($q, $date) => $q->where('report_date', $date))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            // Alasan: filter status dan tanggal ikut terbawa saat pindah halaman, jadi
            // administrator tidak perlu menyaring ulang setelah memeriksa satu laporan.
            ->paginate(15)
            ->withQueryString();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);
        }

        return view('admin.daily-reports.index', compact('reports'));
    }

    public function show(DailyReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['teller', 'approver', 'cashTransfer.approvedBy']);

        $transactions = $report->transactions()->get();

        $auditLogs = AuditLog::where('entity_name', 'daily_report')
            ->where('entity_id', $report->id)
            ->latest('id')
            ->get();

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'transactions' => $transactions,
                    'audit_logs' => $auditLogs,
                ],
            ]);
        }

        return view('admin.daily-reports.show', compact('report', 'transactions', 'auditLogs'));
    }

    public function approve(ApproveReportRequest $request, DailyReport $report)
    {
        $this->authorize('approve', $report);

        try {
            $this->reports->approve($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            AuditLogService::record('daily_report_approval_rejected', 'daily_report', $report->id, [
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil disetujui dan kas dipindahkan ke kas utama.',
                'data' => $report->fresh(['teller', 'approver', 'cashTransfer.approvedBy']),
            ]);
        }

        return redirect()->route('admin.daily-reports.show', $report)
            ->with('success', 'Laporan berhasil disetujui dan kas dipindahkan ke kas utama.');
    }

    public function reject(RejectReportRequest $request, DailyReport $report)
    {
        $this->authorize('reject', $report);

        try {
            $this->reports->reject($report, $request->user(), $request->validated('approval_notes'));
        } catch (TransactionValidationException $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan ditolak. Teller dapat memperbaiki dan mengirim ulang.',
                'data' => $report->fresh(['teller', 'approver']),
            ]);
        }

        return redirect()->route('admin.daily-reports.show', $report)
            ->with('success', 'Laporan ditolak. Teller dapat memperbaiki dan mengirim ulang.');
    }
}
