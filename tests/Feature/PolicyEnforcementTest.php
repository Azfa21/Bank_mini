<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Teller\TellerTestCase;

class PolicyEnforcementTest extends TellerTestCase
{
    private function createTransaction(): Transaction
    {
        return app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-POL-001',
            'slip_amount' => 50000,
        ], $this->teller);
    }

    public function test_teller_can_view_own_transaction_receipt(): void
    {
        $transaction = $this->createTransaction();

        $this->actingAsTeller()
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertOk();
    }

    public function test_other_teller_cannot_view_receipt_of_another_tellers_transaction(): void
    {
        $transaction = $this->createTransaction();

        $otherTeller = User::create([
            'username' => 'teller2',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Dua',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->actingAs($otherTeller, 'web')
            ->get(route('teller.transactions.receipt', $transaction))
            ->assertForbidden();
    }

    public function test_transaction_policy_matrix(): void
    {
        $own = $this->createTransaction();

        $otherTeller = User::create([
            'username' => 'teller2',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller Dua',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->actingAs($this->teller, 'web');
        $this->assertTrue(Gate::allows('view', $own));
        $this->assertTrue(Gate::allows('create', Transaction::class));

        $this->actingAs($otherTeller, 'web');
        $this->assertFalse(Gate::allows('view', $own));

        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('view', $own));
        $this->assertFalse(Gate::allows('create', Transaction::class));
    }

    public function test_daily_report_reject_policy_matrix(): void
    {
        $report = DailyReport::create([
            'report_number' => 'RPT-POL-0001',
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 0,
            'total_deposit' => 100000,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->admin2, 'web');
        $this->assertTrue(Gate::allows('reject', $report));
        $this->assertTrue(Gate::allows('approve', $report));

        foreach ([$this->teller] as $user) {
            $this->actingAs($user, 'web');
            $this->assertFalse(Gate::allows('reject', $report));
        }
    }
}
