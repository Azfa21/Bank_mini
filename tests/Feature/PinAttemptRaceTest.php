<?php

namespace Tests\Feature;

use App\Exceptions\TransactionValidationException;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Teller\TellerTestCase;

class PinAttemptRaceTest extends TellerTestCase
{
    private function service(): TransactionService
    {
        return app(TransactionService::class);
    }

    public function test_verification_uses_fresh_attempts_count_not_stale_model(): void
    {
        DB::table('customers')
            ->where('id', $this->nasabah->id)
            ->update(['failed_pin_attempts' => 2]);

        try {
            $this->service()->verifyCustomerPin($this->nasabah, '000001', 'penarikan', $this->teller->id);
            $this->fail('Seharusnya rekening dikunci pada percobaan ke-3.');
        } catch (TransactionValidationException $e) {
            $this->assertStringContainsString('dikunci selama 15 menit', $e->getMessage());
        }

        $customer = $this->nasabah->refresh();

        $this->assertSame(3, $customer->failed_pin_attempts);
        $this->assertTrue($customer->isPinLocked());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_pin_locked',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_correct_pin_resets_counter_read_from_database(): void
    {
        DB::table('customers')
            ->where('id', $this->nasabah->id)
            ->update(['failed_pin_attempts' => 2]);

        $this->service()->verifyCustomerPin($this->nasabah, '123456', 'penarikan', $this->teller->id);

        $customer = $this->nasabah->refresh();

        $this->assertSame(0, $customer->failed_pin_attempts);
        $this->assertNull($customer->locked_until);
    }

    public function test_wrong_pin_via_http_is_persisted_despite_transaction_rejection(): void
    {
        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-PIN-001',
                'amount' => 5000,
                'pin' => '000000',
            ])
            ->assertRedirect(route('teller.withdrawals.create', $this->account))
            ->assertSessionHas('error');

        $this->assertSame(1, $this->nasabah->refresh()->failed_pin_attempts);
        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_three_wrong_pins_via_http_lock_account(): void
    {
        foreach ([1, 2, 3] as $attempt) {
            $this->actingAsTeller()
                ->from(route('teller.withdrawals.create', $this->account))
                ->post(route('teller.withdrawals.store', $this->account), [
                    'slip_number' => 'SLIP-PIN-00'.$attempt,
                    'amount' => 5000,
                    'pin' => str_pad((string) $attempt, 6, '0', STR_PAD_LEFT),
                ])
                ->assertSessionHas('error');
        }

        $customer = $this->nasabah->refresh();

        $this->assertTrue($customer->isPinLocked());
        $this->assertSame(3, $customer->failed_pin_attempts);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_pin_locked',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_locked_account_rejects_withdrawal_even_with_correct_pin(): void
    {
        $this->nasabah->update([
            'failed_pin_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->actingAsTeller()
            ->from(route('teller.withdrawals.create', $this->account))
            ->post(route('teller.withdrawals.store', $this->account), [
                'slip_number' => 'SLIP-PIN-010',
                'amount' => 5000,
                'pin' => '123456',
            ])
            ->assertSessionHas('error');

        $this->assertSame(25000, $this->account->refresh()->balance);
    }
}
