<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

class AuthorizationMatrixTest extends TellerTestCase
{
    protected function seedTransaction(): Transaction
    {
        return app(TransactionService::class)->deposit($this->account, [
            'slip_number' => 'SLIP-AUTH-001',
            'slip_amount' => 30000,
        ], $this->teller);
    }

    public function test_teller_cannot_open_accounting_statements(): void
    {
        $this->actingAsTeller()
            ->get(route('admin.reports.trial-balance'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_reverse_transaction(): void
    {
        $transaction = $this->seedTransaction();

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.transactions.reverse', $transaction), ['reason' => 'Coba koreksi.'])
            ->assertRedirect();

        $this->assertSame('reversed', $transaction->refresh()->status);
    }

    public function test_admin_cannot_access_teller_registration(): void
    {
        $this->actingAs($this->admin2, 'web')
            ->get(route('teller.members.register'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->admin2, 'web')
            ->post(route('teller.members.register.store'), [
                'full_name' => 'Bukan Tugasnya',
                'class_name' => 'VII A',
                'pin' => '112233',
            ])
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_cannot_reverse_transaction(): void
    {
        $transaction = $this->seedTransaction();

        $this->actingAs($this->nasabah, 'nasabah')
            ->post(route('admin.transactions.reverse', $transaction), ['reason' => 'Coba koreksi.'])
            ->assertRedirect(route('login'));

        $this->assertSame('success', $transaction->refresh()->status);
    }
}
