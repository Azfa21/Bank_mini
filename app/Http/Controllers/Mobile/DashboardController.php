<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Support\MobileTransactionPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        $lastTransaction = null;

        if ($account !== null) {
            $last = Transaction::query()
                ->where(function ($query) use ($account) {
                    $query->where('account_id', $account->id)
                        ->orWhere('recipient_account_id', $account->id);
                })
                ->where('status', 'success')
                ->with(['account.saver', 'recipientAccount.saver'])
                ->orderByDesc('transaction_at')
                ->orderByDesc('id')
                ->first();

            $lastTransaction = $last
                ? MobileTransactionPresenter::one($last, $account->id)
                : null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'student_number' => $customer->student_number,
                    'full_name' => $customer->full_name,
                    'class_name' => $customer->class_name,
                    'phone_number' => $customer->phone_number,
                    'qr_code_value' => $customer->qr_code_value,
                    'status' => $customer->status,
                ],
                'account' => $account === null ? null : [
                    'id' => $account->id,
                    'account_number' => $account->account_number,
                    'academic_year' => $account->academic_year,
                    'opening_balance' => (int) $account->opening_balance,
                    'balance' => (int) $account->balance,
                    'minimum_balance' => (int) $account->minimum_balance,
                    'status' => $account->status,
                    'opened_at' => optional($account->opened_at)->toIso8601String(),
                ],
                'bank' => [
                    'name' => (string) SystemConfig::value('bank_name', 'Bank Mini Sekolah'),
                    'operational_hours' => (string) SystemConfig::value('operational_hours', '07.30 - 14.00 WIB'),
                ],
                'last_transaction' => $lastTransaction,
            ],
        ]);
    }

    public function card(Request $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        return response()->json([
            'success' => true,
            'data' => [
                'bank_name' => (string) SystemConfig::value('bank_name', 'Bank Mini Sekolah'),
                'account_number' => $account?->account_number,
                'student_number' => $customer->student_number,
                'full_name' => $customer->full_name,
                'class_name' => $customer->class_name,
                'qr_code_value' => $customer->qr_code_value,
                'status' => $account?->status,
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
