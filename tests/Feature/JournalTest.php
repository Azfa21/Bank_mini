<?php

namespace Tests\Feature;

use App\Models\JurnalAkuntansi;
use App\Models\Transaction;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class JournalTest extends TellerTestCase
{
    public function test_admin_can_access_journal_audit(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('admin.journals.index'))
            ->assertOk()
            ->assertSee('Audit Jurnal Akuntansi');
    }

    public function test_supervisor_can_access_journal_audit(): void
    {
        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.journals.index'))
            ->assertOk()
            ->assertSee('Audit Jurnal Akuntansi');
    }

    public function test_teller_cannot_access_journal_audit(): void
    {
        $this->actingAsTeller()
            ->get(route('admin.journals.index'))
            ->assertRedirect(route('login'));

        $this->actingAsTeller()
            ->get(route('supervisor.journals.index'))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_cannot_access_journal_audit(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('admin.journals.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('supervisor.journals.index'))
            ->assertRedirect(route('login'));
    }

    public function test_journal_audit_shows_double_entry_rows_for_deposit(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-001',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.journals.index'))
            ->assertOk()
            ->assertSee('101')
            ->assertSee('201')
            ->assertSee('Debit')
            ->assertSee('Kredit')
            ->assertSee($transaction->receipt_number)
            ->assertSee('Rp 100.000');
    }

    public function test_journal_audit_uses_cash_and_savings_account_rules(): void
    {
        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-002',
            'slip_amount' => 50000,
            'physical_cash_amount' => 50000,
            'amount' => 50000,
        ], $this->teller);

        $rows = JurnalAkuntansi::where('transaction_id', Transaction::latest('id')->value('id'))->get();

        $this->assertSame(2, $rows->count());
        $this->assertSame(50000, $rows->sum('debit_amount'));
        $this->assertSame(50000, $rows->sum('credit_amount'));
        $this->assertContains('101', $rows->pluck('account_code')->all());
        $this->assertContains('201', $rows->pluck('account_code')->all());
    }

    public function test_journal_audit_totals_debit_and_credit_are_balanced(): void
    {
        app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-003',
            'slip_amount' => 75000,
            'physical_cash_amount' => 75000,
            'amount' => 75000,
        ], $this->teller);

        app(TransactionService::class)->withdraw($this->account, [
            'slip_number' => 'SLIP-J-004',
            'amount' => 25000,
        ], '123456', $this->teller);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.journals.index'))
            ->assertOk()
            ->assertSee('Rp 100.000');

        $totalDebit = (int) JurnalAkuntansi::sum('debit_amount');
        $totalCredit = (int) JurnalAkuntansi::sum('credit_amount');

        $this->assertSame($totalDebit, $totalCredit);
    }

    public function test_journal_audit_can_filter_by_transaction_number(): void
    {
        $first = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-005',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $second = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-006',
            'slip_amount' => 200000,
            'physical_cash_amount' => 200000,
            'amount' => 200000,
        ], $this->teller);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.journals.index', ['transaction_number' => $first->receipt_number]))
            ->assertOk()
            ->assertSee($first->receipt_number)
            ->assertDontSee($second->receipt_number);
    }

    public function test_journal_audit_can_filter_by_account_code(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-007',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        $this->actingAs($this->supervisor, 'web')
            ->get(route('supervisor.journals.index', ['account_code' => '101']))
            ->assertOk()
            ->assertSee($transaction->receipt_number.'-01')
            ->assertDontSee($transaction->receipt_number.'-02');
    }

    public function test_journal_audit_can_filter_by_date_range(): void
    {
        $transaction = app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-J-008',
            'slip_amount' => 100000,
            'physical_cash_amount' => 100000,
            'amount' => 100000,
        ], $this->teller);

        JurnalAkuntansi::query()->update(['journal_at' => now()->subDays(5)]);

        $this->actingAs($this->admin, 'web')
            ->get(route('admin.journals.index', [
                'date_from' => now()->startOfDay()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertDontSee($transaction->receipt_number);
    }
}
