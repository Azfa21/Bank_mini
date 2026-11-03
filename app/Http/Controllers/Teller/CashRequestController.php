<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teller\StoreCashRequestRequest;
use App\Models\CashRequest;
use App\Models\DailyReport;
use App\Services\AuditLogService;
use App\Services\CashRequestService;
use App\Services\DailyReportService;
use Illuminate\Http\Request;

class CashRequestController extends Controller
{
    public function __construct(
        protected CashRequestService $requests,
        protected DailyReportService $reports,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CashRequest::class);

        // Saldo laci hari ini dipakai teller menilai modal yang masih tersisa.
        $recap = $this->reports->recap($request->user(), now()->toDateString());

        $cashRequests = CashRequest::where('teller_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $pendingCount = $cashRequests->where('status', 'pending')->count();

        // Formulir ditutup setelah laporan penutupan hari ini dikirim; laci sudah
        // dihitung dan tidak boleh menerima kas baru sampai laporan diputuskan.
        $closedReport = DailyReport::where('teller_id', $request->user()->id)
            ->whereDate('report_date', now()->toDateString())
            ->whereIn('status', ['submitted', 'approved'])
            ->first();

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'recap' => $recap,
                    'cash_requests' => $cashRequests,
                    'closed_report' => $closedReport,
                ],
            ]);
        }

        return view('teller.cash-requests.index', compact('recap', 'cashRequests', 'pendingCount', 'closedReport'));
    }

    public function store(StoreCashRequestRequest $request)
    {
        try {
            $cashRequest = $this->requests->submit(
                $request->user(),
                (int) $request->validated('amount'),
            );
        } catch (TransactionValidationException $e) {
            AuditLogService::record('cash_request_creation_blocked', 'cash_request', null, [
                'amount' => $request->validated('amount'),
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
                'message' => 'Permintaan kas dikirim ke Administrator.',
                'data' => $cashRequest,
            ], 201);
        }

        return redirect()->route('teller.cash-requests.index')
            ->with('success', 'Permintaan kas '.$cashRequest->request_number.' dikirim ke Administrator.');
    }
}
