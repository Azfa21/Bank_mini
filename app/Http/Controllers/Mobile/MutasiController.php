<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Saver;
use App\Models\Transaction;
use App\Support\MobileTransactionPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        if ($account === null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'account' => null,
                    'transactions' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 15,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ],
            ]);
        }

        $perPage = max(1, min(50, $request->integer('per_page', 15)));

        $paginator = Transaction::query()
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->input('date_from'), fn ($query, $date) => $query->whereDate('transaction_at', '>=', $date))
            ->when($request->input('date_to'), fn ($query, $date) => $query->whereDate('transaction_at', '<=', $date))
            ->with(['account.saver', 'recipientAccount.saver'])
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'balance' => (int) $account->balance,
                    'minimum_balance' => (int) $account->minimum_balance,
                    'status' => $account->status,
                ],
                'transactions' => MobileTransactionPresenter::collection($paginator->items(), $account->id),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    public function statement(Request $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        if ($account === null) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening tidak ditemukan.',
            ], 404);
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $transactions = Transaction::query()
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->whereDate('transaction_at', '>=', $dateFrom)
            ->whereDate('transaction_at', '<=', $dateTo)
            ->with(['account.saver', 'recipientAccount.saver'])
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $summary = collect(DB::table('transactions')
            ->where(function ($query) use ($account) {
                $query->where('account_id', $account->id)
                    ->orWhere('recipient_account_id', $account->id);
            })
            ->where('status', 'success')
            ->whereDate('transaction_at', '>=', $dateFrom)
            ->whereDate('transaction_at', '<=', $dateTo)
            ->selectRaw(
                'type, SUM(CASE WHEN account_id = ? THEN amount ELSE 0 END) as total_out, SUM(CASE WHEN recipient_account_id = ? THEN amount ELSE 0 END) as total_in',
                [$account->id, $account->id]
            )
            ->groupBy('type')
            ->get())
            ->mapWithKeys(fn ($row) => [
                $row->type => ['out' => (int) $row->total_out, 'in' => (int) $row->total_in],
            ]);

        $totalDeposit = $summary['deposit']['out'] ?? 0;
        $totalWithdrawal = $summary['withdrawal']['out'] ?? 0;
        $totalTransferOut = $summary['transfer']['out'] ?? 0;
        $totalTransferIn = $summary['transfer']['in'] ?? 0;

        $verificationCode = 'EST-'.strtoupper(substr(
            hash('sha256', $account->id.'|'.$dateFrom.'|'.$dateTo.'|'.$transactions->count().'|'.$totalDeposit.'|'.$totalWithdrawal),
            0,
            12
        ));

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'balance' => (int) $account->balance,
                    'minimum_balance' => (int) $account->minimum_balance,
                    'status' => $account->status,
                ],
                'customer' => [
                    'full_name' => $customer->full_name,
                    'student_number' => $customer->student_number,
                    'class_name' => $customer->class_name,
                ],
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'verification_code' => $verificationCode,
                'summary' => [
                    'total_deposit' => $totalDeposit,
                    'total_withdrawal' => $totalWithdrawal,
                    'total_transfer_out' => $totalTransferOut,
                    'total_transfer_in' => $totalTransferIn,
                ],
                'transactions' => MobileTransactionPresenter::collection($transactions, $account->id),
            ],
        ]);
    }

    protected function saver(Request $request): Saver
    {
        /** @var Saver $customer */
        $customer = $request->attributes->get('mobile_customer');

        return $customer;
    }
}
