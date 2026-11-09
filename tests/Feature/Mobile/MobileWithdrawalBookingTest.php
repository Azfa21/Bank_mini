<?php

namespace Tests\Feature\Mobile;

use App\Models\SystemConfig;
use App\Models\WithdrawalBooking;
use App\Services\WithdrawalBookingService;
use Carbon\CarbonImmutable;

class MobileWithdrawalBookingTest extends MobileTestCase
{
    public function test_overview_returns_balance_and_minimum_balance_and_limits(): void
    {
        $token = $this->loginAndGetToken();

        $response = $this->withMobileToken($token)
            ->getJson(route('api.mobile.withdrawal-bookings.index'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'daily_limit',
                    'today_withdrawn',
                    'today_limit',
                    'today_remaining',
                    'today_booking',
                    'balance',
                    'minimum_balance',
                    'max_withdrawable',
                    'bookings',
                ],
            ]);
    }

    public function test_booking_rejected_when_amount_exceeds_balance(): void
    {
        $token = $this->loginAndGetToken();

        // Saldo nasabah 500.000, mengajukan booking 1.500.000 (di atas 1.000.000).
        $this->account->update(['balance' => 500000]);

        $tomorrow = CarbonImmutable::now(WithdrawalBookingService::BUSINESS_TIMEZONE)
            ->addDay()
            ->toDateString();

        $response = $this->withMobileToken($token)
            ->postJson(route('api.mobile.withdrawal-bookings.store'), [
                'withdrawal_date' => $tomorrow,
                'amount' => 1500000,
                'notes' => 'Rencana keperluan pribadi',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
        $this->assertDatabaseMissing('withdrawal_bookings', [
            'account_id' => $this->account->id,
            'amount' => 1500000,
        ]);
    }

    public function test_booking_rejected_when_balance_after_withdrawal_violates_minimum_balance(): void
    {
        $token = $this->loginAndGetToken();

        // Saldo nasabah 1.005.000, batas saldo mengendap 10.000.
        // Jika menarik 1.001.000, sisa saldo menjadi 4.000 (< 10.000).
        SystemConfig::query()->updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => '10000']
        );
        $this->account->update([
            'balance' => 1005000,
            'minimum_balance' => 10000,
        ]);

        $tomorrow = CarbonImmutable::now(WithdrawalBookingService::BUSINESS_TIMEZONE)
            ->addDay()
            ->toDateString();

        $response = $this->withMobileToken($token)
            ->postJson(route('api.mobile.withdrawal-bookings.store'), [
                'withdrawal_date' => $tomorrow,
                'amount' => 1001000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
        $this->assertDatabaseMissing('withdrawal_bookings', [
            'account_id' => $this->account->id,
            'amount' => 1001000,
        ]);
    }

    public function test_booking_succeeds_when_balance_is_sufficient_and_respects_minimum_balance(): void
    {
        $token = $this->loginAndGetToken();

        // Saldo nasabah 2.000.000, batas saldo mengendap 10.000.
        // Mengajukan 1.500.000, sisa saldo 500.000 (>= 10.000).
        $this->account->update([
            'balance' => 2000000,
            'minimum_balance' => 10000,
        ]);

        $tomorrow = CarbonImmutable::now(WithdrawalBookingService::BUSINESS_TIMEZONE)
            ->addDay()
            ->toDateString();

        $response = $this->withMobileToken($token)
            ->postJson(route('api.mobile.withdrawal-bookings.store'), [
                'withdrawal_date' => $tomorrow,
                'amount' => 1500000,
                'notes' => 'Keperluan pendaftaran',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 1500000)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('withdrawal_bookings', [
            'account_id' => $this->account->id,
            'amount' => 1500000,
            'status' => 'pending',
        ]);
    }

    public function test_teller_view_shows_account_balance_and_cannot_approve_insufficient_balance(): void
    {
        $tomorrow = CarbonImmutable::now(WithdrawalBookingService::BUSINESS_TIMEZONE)
            ->addDay()
            ->toDateString();

        $this->account->update([
            'balance' => 2000000,
            'minimum_balance' => 10000,
        ]);

        $booking = WithdrawalBooking::query()->create([
            'account_id' => $this->account->id,
            'withdrawal_date' => $tomorrow,
            'amount' => 1500000,
            'status' => WithdrawalBooking::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->teller)
            ->get(route('teller.withdrawal-bookings.index'));

        $response->assertOk();
        $response->assertSee('Saldo saat ini');
        $response->assertSee('Rp 2.000.000');

        // Kurangi saldo rekening menjadi 500.000 sebelum teller menyetujui
        $this->account->update(['balance' => 500000]);

        $approveResponse = $this->actingAs($this->teller)
            ->post(route('teller.withdrawal-bookings.approve', $booking));

        $approveResponse->assertSessionHas('error');
        $this->assertSame(WithdrawalBooking::STATUS_PENDING, $booking->fresh()->status);
    }
}

