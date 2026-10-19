<?php

namespace Tests\Feature\Nasabah;

use App\Models\Account;
use App\Models\Saver;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class StatementTest extends NasabahTestCase
{
    private TransactionService $service;

    private Saver $otherCustomer;

    private Account $otherAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TransactionService::class);

        $this->otherCustomer = Saver::create([
            'student_number' => '2023002',
            'full_name' => 'Beno',
            'class_name' => 'X RPL 2',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->otherAccount = Account::create([
            'account_number' => '2026/2027-002',
            'saver_id' => $this->otherCustomer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => '2026/2027',
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }

    private function seedMutations(): void
    {
        $this->service->deposit($this->account, [
            'slip_number' => 'SLIP-ST-001',
            'slip_amount' => 100000,
        ], $this->teller);

        $this->service->withdraw($this->account, [
            'amount' => 30000,
            'slip_number' => 'SLIP-ST-002',
        ], '123456', $this->teller);

        $this->service->transfer($this->account, $this->otherAccount, [
            'amount' => 20000,
            'notes' => 'Transfer statement test',
        ], '123456', $this->teller);
    }

    public function test_statement_renders_with_summary_totals(): void
    {
        $this->seedMutations();

        $response = $this->actingAsNasabah()
            ->get(route('nasabah.statement'))
            ->assertOk()
            ->assertSee('Rekening Koran Nasabah (e-Statement)')
            ->assertSee($this->account->account_number)
            ->assertSee('Total Setoran: Rp 100.000')
            ->assertSee('Total Penarikan: Rp 30.000')
            ->assertSee('Total Transfer Keluar: Rp 20.000');

        $this->assertSame(0, (int) $response->viewData('totalTransferIn'));
    }

    public function test_statement_shows_incoming_transfer_for_recipient(): void
    {
        $this->seedMutations();

        $this->actingAs($this->otherCustomer, 'nasabah')
            ->get(route('nasabah.statement'))
            ->assertOk()
            ->assertSee('Total Transfer Masuk: Rp 20.000');
    }

    public function test_statement_verification_code_matches_expected_formula(): void
    {
        $this->seedMutations();

        $response = $this->actingAsNasabah()
            ->get(route('nasabah.statement'))
            ->assertOk();

        $dateFrom = now()->startOfMonth()->toDateString();
        $dateTo = now()->toDateString();

        $expectedCode = 'EST-'.strtoupper(substr(
            hash('sha256', $this->account->id.'|'.$dateFrom.'|'.$dateTo.'|3|100000|30000'),
            0,
            12
        ));

        $this->assertSame($expectedCode, $response->viewData('verificationCode'));
    }

    public function test_statement_filters_by_period(): void
    {
        $this->seedMutations();

        $futureFrom = now()->addDay()->toDateString();
        $futureTo = now()->addWeek()->toDateString();

        $this->actingAsNasabah()
            ->get(route('nasabah.statement', ['date_from' => $futureFrom, 'date_to' => $futureTo]))
            ->assertOk()
            ->assertSee('Tidak ada mutasi pada periode ini.');
    }

    public function test_statement_json_returns_structured_data(): void
    {
        $this->seedMutations();

        $this->actingAsNasabah()
            ->getJson(route('nasabah.statement'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_deposit', 100000)
            ->assertJsonPath('data.summary.total_withdrawal', 30000)
            ->assertJsonPath('data.summary.total_transfer_out', 20000)
            ->assertJsonCount(3, 'data.transactions');
    }

    public function test_nasabah_without_account_gets_404(): void
    {
        $orphanCustomer = Saver::create([
            'student_number' => '2023099',
            'full_name' => 'Tanpa Rekening',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-99',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->actingAs($orphanCustomer, 'nasabah')
            ->get(route('nasabah.statement'))
            ->assertNotFound();
    }

    public function test_staff_cannot_access_nasabah_statement(): void
    {
        $this->seedMutations();

        foreach ([$this->admin, $this->teller] as $user) {
            $this->actingAs($user, 'web')
                ->get(route('nasabah.statement'))
                ->assertRedirect(route('login'));
        }
    }
}
