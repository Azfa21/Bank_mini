<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DecideCashRequestRequest;
use App\Models\CashRequest;
use App\Models\JurnalAkuntansi;
use App\Services\AccountingService;
use App\Services\AuditLogService;
use App\Services\CashRequestService;
use Illuminate\Http\Request;

class CashRequestController extends Controller
{
    public function __construct(
        protected CashRequestService $requests,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CashRequest::class);

        $pending = CashRequest::where('status', 'pending')
            ->with('teller')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $history = CashRequest::whereIn('status', ['approved', 'rejected'])
            ->with(['teller', 'approvedBy'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Saldo pos kas dihitung dari jurnal supaya angka yang dilihat admin
        // sama dengan angka yang dipakai pembukuan (bukan estimasi).
        $balances = JurnalAkuntansi::whereIn('account_code', [
            AccountingService::ACCOUNT_CODE_CASH_TELLER,
            AccountingService::ACCOUNT_CODE_CASH_VAULT,
        ])
            ->selectRaw('account_code, COALESCE(SUM(debit_amount), 0) - COALESCE(SUM(credit_amount), 0) as balance')
            ->groupBy('account_code')
            ->pluck('balance', 'account_code');

        $mainCashBalance = (int) ($balances[AccountingService::ACCOUNT_CODE_CASH_VAULT] ?? 0);
        $drawerCashBalance = (int) ($balances[AccountingService::ACCOUNT_CODE_CASH_TELLER] ?? 0);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'pending' => $pending,
                    'history' => $history,
                    'main_cash_balance' => $mainCashBalance,
                    'drawer_cash_balance' => $drawerCashBalance,
                ],
            ]);
        }

        return view('admin.cash-requests.index', compact(
            'pending',
            'history',
            'mainCashBalance',
            'drawerCashBalance'
        ));
    }

    public function approve(DecideCashRequestRequest $request, CashRequest $cashRequest)
    {
        $this->authorize('approve', $cashRequest);

        try {
            $this->requests->approve($cashRequest, $request->user(), $request->validated('decision_notes'));
        } catch (TransactionValidationException $e) {
            return $this->decisionBlocked($request, $cashRequest, $e->getMessage());
        }

        $message = 'Permintaan kas disetujui. Jurnal pengiriman kas ke laci teller telah dibuat.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $cashRequest->fresh(['teller', 'approvedBy']),
            ]);
        }

        return redirect()->route('admin.cash-requests.index')->with('success', $message);
    }

    public function reject(DecideCashRequestRequest $request, CashRequest $cashRequest)
    {
        $this->authorize('reject', $cashRequest);

        try {
            $this->requests->reject($cashRequest, $request->user(), $request->validated('decision_notes'));
        } catch (TransactionValidationException $e) {
            return $this->decisionBlocked($request, $cashRequest, $e->getMessage());
        }

        $message = 'Permintaan kas ditolak. Teller dapat mengajukan kembali bila diperlukan.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $cashRequest->fresh(['teller', 'approvedBy']),
            ]);
        }

        return redirect()->route('admin.cash-requests.index')->with('success', $message);
    }

    protected function decisionBlocked(Request $request, CashRequest $cashRequest, string $reason)
    {
        AuditLogService::record('cash_request_decision_blocked', 'cash_request', $cashRequest->id, [
            'reason' => $reason,
        ], $request->user()->id);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 422);
        }

        return back()->with('error', $reason);
    }
}
