<?php

namespace App\Http\Controllers\Mobile;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreWithdrawalBookingRequest;
use App\Models\Saver;
use App\Models\WithdrawalBooking;
use App\Services\WithdrawalBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalBookingController extends Controller
{
    public function __construct(
        protected WithdrawalBookingService $bookings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        if ($account === null) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening aktif tidak ditemukan.',
            ], 422);
        }

        $daily = $this->bookings->dailyStatus($account);
        $minimumBalance = $this->bookings->minimumBalanceFor($account);
        $balance = (int) $account->balance;
        $maxWithdrawable = max(0, $balance - $minimumBalance);

        $bookings = WithdrawalBooking::query()
            ->where('account_id', $account->id)
            ->latest('withdrawal_date')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (WithdrawalBooking $booking) => $this->present($booking));

        return response()->json([
            'success' => true,
            'data' => [
                'daily_limit' => WithdrawalBookingService::DAILY_LIMIT,
                'today_withdrawn' => $daily['withdrawn'],
                'today_limit' => $daily['limit'],
                'today_remaining' => $daily['remaining'],
                'today_booking' => $daily['booking'] ? $this->present($daily['booking']) : null,
                'balance' => $balance,
                'minimum_balance' => $minimumBalance,
                'max_withdrawable' => $maxWithdrawable,
                'bookings' => $bookings,
            ],
        ]);
    }

    public function store(StoreWithdrawalBookingRequest $request): JsonResponse
    {
        $customer = $this->saver($request);
        $account = $customer->account;

        if ($account === null) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening aktif tidak ditemukan.',
            ], 422);
        }

        $data = $request->validated();

        try {
            $booking = $this->bookings->requestBooking(
                $account,
                $data['withdrawal_date'],
                (int) $data['amount'],
                $data['notes'] ?? null,
            );
        } catch (TransactionValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking dikirim dan menunggu persetujuan teller.',
            'data' => $this->present($booking),
        ], 201);
    }

    private function saver(Request $request): Saver
    {
        /** @var Saver $customer */
        $customer = $request->attributes->get('mobile_customer');

        return $customer;
    }

    /** @return array<string, mixed> */
    private function present(WithdrawalBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'withdrawal_date' => $booking->withdrawal_date->toDateString(),
            'amount' => (int) $booking->amount,
            'status' => $booking->status,
            'status_label' => $booking->statusLabel(),
            'notes' => $booking->notes,
            'decision_note' => $booking->decision_note,
            'created_at' => $booking->created_at?->toIso8601String(),
            'decided_at' => $booking->decided_at?->toIso8601String(),
        ];
    }
}
