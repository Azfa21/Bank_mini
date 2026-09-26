<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreClosingRequest;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\DailyReportService;
use Illuminate\Http\Request;

class ClosingController extends Controller
{
    public function __construct(
        protected DailyReportService $reports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DailyReport::class);

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

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'recap' => $recap,
                    'report' => $report,
                    'transactions' => $transactions,
                ],
            ]);
        }

        return view('teller.closing', compact('date', 'recap', 'report', 'transactions'));
    }

    public function store(StoreClosingRequest $request)
    {
        $this->authorize('create', DailyReport::class);

        $date = $request->validated('report_date');

        // Total fisik yang diketik Teller adalah sumber kebenaran.
        // Rincian pecahan hanya alat bantu hitung (opsional) dan bukti audit.
        $denominations = $request->validated('denominations') ?? [];
        $physicalCash = (int) ($request->validated('physical_cash') ?? 0);
        $differenceNote = $request->validated('difference_note');

        try {
            $report = $this->reports->createReport($request->user(), $date, $physicalCash, $denominations, $differenceNote);
        } catch (TransactionValidationException $e) {
            AuditLogService::record('daily_report_creation_blocked', 'daily_report', null, [
                'report_date' => $date,
                'reason' => $e->getMessage(),
            ], $request->user()->id);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan penutupan kas berhasil disimpan.',
                'data' => $report->load(['teller', 'approver']),
            ], 201);
        }

        return redirect()->route('teller.closing.print', $report);
    }

    public function print(DailyReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['teller', 'approver']);

        if ($this->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('teller.report-print', compact('report'));
    }
}
