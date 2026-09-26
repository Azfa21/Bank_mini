<?php

namespace Tests\Feature\Teller;

use App\Models\Account;
use App\Models\Saver;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Hash;

class AccountMinimumBalanceTest extends TellerTestCase
{
    public function test_withdrawal_rejects_going_below_account_specific_minimum(): void
    {
        $this->account->update(['minimum_balance' => 20000]);

        $this->actingAsTeller()
            ->postJson(route('teller.withdrawals.store', $this->account), [
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Saldo setelah penarikan tidak boleh kurang dari Rp 20,000.');

        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_transfer_rejects_going_below_account_specific_minimum(): void
    {
        $destination = $this->createDestinationAccount();
        $this->account->update(['minimum_balance' => 20000]);

        $this->actingAsTeller()
            ->postJson(route('teller.transfers.store', $this->account), [
                'recipient_account_number' => $destination->account_number,
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Saldo pengirim setelah transfer tidak boleh kurang dari Rp 20,000.');

        $this->assertSame(25000, $this->account->refresh()->balance);
        $this->assertSame(0, $destination->refresh()->balance);
    }

    public function test_stricter_global_minimum_remains_enforced(): void
    {
        SystemConfig::updateOrCreate(
            ['config_key' => 'minimum_balance'],
            ['config_value' => '30000']
        );

        $this->actingAsTeller()
            ->postJson(route('teller.withdrawals.store', $this->account), [
                'amount' => 10000,
                'pin' => '123456',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Saldo setelah penarikan tidak boleh kurang dari Rp 30,000.');

        $this->assertSame(25000, $this->account->refresh()->balance);
    }

    public function test_teller_forms_display_the_account_specific_minimum(): void
    {
        $this->account->update(['minimum_balance' => 20000]);

        $this->actingAsTeller()
            ->get(route('teller.withdrawals.create', $this->account))
            ->assertOk()
            ->assertSee('Rp 20.000');

        $this->actingAsTeller()
            ->get(route('teller.transfers.create', $this->account))
            ->assertOk()
            ->assertSee('Rp 20.000');
    }

    private function createDestinationAccount(): Account
    {
        $saver = Saver::create([
            'student_number' => '2023002',
            'full_name' => 'B',
            'class_name' => 'X RPL 1',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        return Account::create([
            'account_number' => '2026/2027-002',
            'saver_id' => $saver->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}