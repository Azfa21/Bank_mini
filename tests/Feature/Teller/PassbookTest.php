<?php

namespace Tests\Feature\Teller;

use App\Models\Transaction;
use App\Services\TransactionService;

class PassbookTest extends TellerTestCase
{
    private function seedTransactions(int $count): void
    {
        $service = app(TransactionService::class);

        foreach (range(1, $count) as $i) {
            $service->deposit($this->account, [
                'slip_number' => 'SLIP-PB-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'slip_amount' => 1000 * $i,
                'physical_cash_amount' => 1000 * $i,
            ], $this->teller);
        }
    }

    public function test_show_page_lists_passbook_transactions(): void
    {
        $this->seedTransactions(3);

        $response = $this->actingAsTeller()
            ->get(route('teller.passbook.show', $this->account))
            ->assertOk()
            ->assertSee('Buku Tabungan Fisik')
            ->assertSee($this->account->account_number)
            ->assertSee('Setoran');

        $transactions = $response->viewData('transactions');

        $this->assertSame(3, $transactions->count());
    }

    public function test_show_paginates_twenty_rows_per_page(): void
    {
        $this->seedTransactions(21);

        $response = $this->actingAsTeller()
            ->get(route('teller.passbook.show', ['account' => $this->account, 'page' => 2]))
            ->assertOk();

        $viewTransactions = $response->viewData('transactions');

        $this->assertSame(1, $viewTransactions->count());
        $this->assertEquals(2, $viewTransactions->lastPage());
    }

    public function test_print_renders_from_requested_start_line(): void
    {
        $this->seedTransactions(3);

        $response = $this->actingAsTeller()
            ->get(route('teller.passbook.print', ['account' => $this->account, 'start_line' => 5]))
            ->assertOk()
            ->assertSee('Baris cetak: 5–7');

        $printed = $response->viewData('transactions');

        $this->assertCount(3, $printed);

        $content = $response->getContent();

        $this->assertSame(1, preg_match(
            '/>\s*5\s*<\/td>\s*<td[^>]*>\d{2}\/\d{2}\/\d{4}<\/td>/',
            $content
        ), 'Transaksi pertama harus tercetak pada baris buku ke-5.');
        $this->assertStringContainsString($printed->first()->receipt_number, $content);
        $this->assertStringNotContainsString('>24<', $content);
    }

    public function test_print_limits_rows_to_fit_remaining_page_lines(): void
    {
        $this->seedTransactions(10);

        $response = $this->actingAsTeller()
            ->get(route('teller.passbook.print', ['account' => $this->account, 'start_line' => 15]))
            ->assertOk();

        $printed = $response->viewData('transactions');

        $this->assertCount(6, $printed);
        $this->assertEquals(
            Transaction::where('account_id', $this->account->id)->orderBy('id')->first()->id,
            $printed->first()->id
        );
    }

    public function test_print_clamps_start_line_into_valid_range(): void
    {
        $this->seedTransactions(2);

        $lowResponse = $this->actingAsTeller()
            ->get(route('teller.passbook.print', ['account' => $this->account, 'start_line' => 0]))
            ->assertOk();

        $this->assertSame(1, $lowResponse->viewData('startLine'));

        $highResponse = $this->actingAsTeller()
            ->get(route('teller.passbook.print', ['account' => $this->account, 'start_line' => 99]))
            ->assertOk();

        $this->assertSame(20, $highResponse->viewData('startLine'));
        $this->assertCount(1, $highResponse->viewData('transactions'));
    }

    public function test_passbook_json_returns_account_and_transactions(): void
    {
        $this->seedTransactions(2);

        $this->actingAsTeller()
            ->getJson(route('teller.passbook.show', $this->account))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.transactions.data');
    }

    public function test_admin_cannot_open_passbook(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get(route('teller.passbook.show', $this->account))
            ->assertRedirect(route('login'));
    }

    public function test_nasabah_cannot_open_passbook(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get(route('teller.passbook.show', $this->account))
            ->assertRedirect(route('login'));
    }
}
