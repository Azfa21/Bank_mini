<?php

namespace App\Http\Controllers\Teller;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalBooking;
use App\Services\WithdrawalBookingService;
use Illuminate\Http\Request;

class WithdrawalBookingController extends Controller
{
    public function __construct(
        protected WithdrawalBookingService $bookings,
    ) {}

    public function index()
    {
        $bookings = WithdrawalBooking::query()
            ->with(['account.saver', 'decider'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderBy('withdrawal_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('teller.withdrawal-bookings.index', compact('bookings'));
    }

    public function approve(Request $request, WithdrawalBooking $booking)
    {
        try {
            $this->bookings->approve($booking, $request->user());
        } catch (TransactionValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking penarikan disetujui.');
    }

    public function reject(Request $request, WithdrawalBooking $booking)
    {
        $data = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->bookings->reject($booking, $request->user(), $data['decision_note'] ?? null);
        } catch (TransactionValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Booking penarikan ditolak.');
    }
}
