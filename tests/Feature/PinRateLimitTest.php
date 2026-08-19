<?php

namespace Tests\Feature;

use App\Exceptions\TransactionValidationException;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class PinRateLimitTest extends TellerTestCase
{
    private function service(): TransactionService
    {
        return app(TransactionService::class);
    }

    public function test_wrong_pin_increments_failed_attempts(): void
    {
        try {
            $this->service()->verifyCustomerPin($this->nasabah, '000001', 'penarikan', $this->teller->id);
        } catch (TransactionValidationException $e) {
            $this->assertStringContainsString('PIN nasabah salah', $e->getMessage());
        }

        $this->assertSame(1, $this->nasabah->refresh()->failed_pin_attempts);
    }

    public function test_third_wrong_pin_locks_account_for_15_minutes(): void
    {
        foreach (['000001', '000002', '000003'] as $pin) {
            try {
                $this->service()->verifyCustomerPin($this->nasabah, $pin, 'penarikan', $this->teller->id);
            } catch (TransactionValidationException $e) {
                // expected
            }
        }

        $customer = $this->nasabah->refresh();

        $this->assertSame(3, $customer->failed_pin_attempts);
        $this->assertNotNull($customer->locked_until);
        $this->assertTrue($customer->isPinLocked());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_pin_locked',
            'entity_id' => $customer->id,
        ]);
    }

    public function test_locked_account_rejects_even_correct_pin(): void
    {
        $this->nasabah->update([
            'failed_pin_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        try {
            $this->service()->verifyCustomerPin($this->nasabah, '123456', 'penarikan', $this->teller->id);
            $this->fail('Seharusnya transaksi ditolak karena PIN terkunci.');
        } catch (TransactionValidationException $e) {
            $this->assertStringContainsString('terkunci sementara', $e->getMessage());
        }
    }

    public function test_correct_pin_resets_failed_attempts(): void
    {
        $this->nasabah->update([
            'failed_pin_attempts' => 2,
            'locked_until' => null,
        ]);

        $this->service()->verifyCustomerPin($this->nasabah, '123456', 'penarikan', $this->teller->id);

        $customer = $this->nasabah->refresh();

        $this->assertSame(0, $customer->failed_pin_attempts);
        $this->assertNull($customer->locked_until);
    }

    public function test_pin_attempts_reset_after_lock_expired(): void
    {
        $this->nasabah->update([
            'failed_pin_attempts' => 3,
            'locked_until' => now()->subMinute(),
        ]);

        $this->service()->verifyCustomerPin($this->nasabah, '123456', 'penarikan', $this->teller->id);

        $customer = $this->nasabah->refresh();

        $this->assertFalse($customer->isPinLocked());
        $this->assertSame(0, $customer->failed_pin_attempts);
    }

    public function test_pin_reset_via_forgot_password_unlocks_account(): void
    {
        $this->nasabah->update([
            'failed_pin_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->from(route('password.request'))
            ->post(route('password.nasabah.reset'), [
                'student_number' => $this->nasabah->student_number,
                'class_name' => $this->nasabah->class_name,
                'pin' => '999999',
                'pin_confirmation' => '999999',
            ])
            ->assertRedirect(route('login'));

        $customer = $this->nasabah->refresh();

        $this->assertSame(0, $customer->failed_pin_attempts);
        $this->assertNull($customer->locked_until);
        $this->assertFalse($customer->isPinLocked());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_success',
            'entity_id' => $customer->id,
        ]);
    }
}
