<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Services\SequenceService;
use RuntimeException;
use Tests\Feature\Teller\TellerTestCase;

class CustomerCreationTest extends TellerTestCase
{
    private function compactAcademicYear(): string
    {
        return now()->format('y').((int) now()->format('y') + 1);
    }

    private function expectedAccountNumber(int $ordinal): string
    {
        return '10'.$this->compactAcademicYear().str_pad((string) $ordinal, 4, '0', STR_PAD_LEFT);
    }

    public function test_store_generates_student_number_and_account_in_sequence(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII A',
                'phone_number' => '081234567777',
                'email' => 'budi.santoso@example.test',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('full_name', 'Budi Santoso')->firstOrFail();
        $account = Account::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(now()->format('Y').'0001', $customer->student_number);
        $this->assertSame($this->expectedAccountNumber(1), $account->account_number);
        $this->assertSame(now()->format('Y').'/'.(now()->format('Y') + 1), $account->academic_year);
        $this->assertSame(0, $account->balance);
        $this->assertSame(10000, $account->minimum_balance);
        $this->assertSame('active', $account->status);
    }

    public function test_consecutive_creations_produce_sequential_numbers(): void
    {
        foreach ([['Citra Lestari'], ['Dewi Anggraini']] as [$name]) {
            $this->actingAs($this->admin, 'web')
                ->post(route('admin.customers.store'), [
                    'full_name' => $name,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
                    'pin' => '123456',
                ])
                ->assertRedirect(route('admin.customers.index'));
        }

        $customers = Customer::whereIn('full_name', ['Citra Lestari', 'Dewi Anggraini'])
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $customers);
        $this->assertSame(
            [now()->format('Y').'0001', now()->format('Y').'0002'],
            $customers->pluck('student_number')->all(),
        );

        $numbers = Account::whereIn('customer_id', $customers->pluck('id'))
            ->orderBy('customer_id')
            ->pluck('account_number')
            ->all();

        $this->assertSame([$this->expectedAccountNumber(1), $this->expectedAccountNumber(2)], $numbers);
    }

    public function test_new_numbering_is_independent_of_legacy_format_accounts(): void
    {
        $legacyCustomer = Customer::create([
            'student_number' => 'LEGACY001',
            'full_name' => 'Nasabah Lama',
            'class_name' => 'IX B',
            'phone_number' => '081234560000',
            'pin_hash' => '123456',
            'qr_code_value' => 'qr-legacy-001',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        Account::create([
            'account_number' => now()->format('Y').'/'.(now()->format('Y') + 1).'-009',
            'customer_id' => $legacyCustomer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => now()->format('Y').'/'.(now()->format('Y') + 1),
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'full_name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@example.test',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $newCustomer = Customer::where('full_name', 'Eko Prasetyo')->firstOrFail();
        $newAccount = Account::where('customer_id', $newCustomer->id)->firstOrFail();

        $this->assertSame($this->expectedAccountNumber(1), $newAccount->account_number);
    }

    public function test_customer_can_edit_generated_student_number(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'full_name' => 'Fajar Nugroho',
                'email' => 'fajar.nugroho@example.test',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('full_name', 'Fajar Nugroho')->firstOrFail();
        $this->assertSame(now()->format('Y').'0001', $customer->student_number);

        $this->actingAs($this->admin, 'web')
            ->put(route('admin.customers.update', $customer), [
                'student_number' => '20235999',
                'full_name' => 'Fajar Nugroho',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'student_number' => '20235999',
        ]);
    }

    public function test_customer_is_rolled_back_when_account_opening_fails(): void
    {
        $this->mock(SequenceService::class, function ($mock) {
            $mock->shouldReceive('next')->andReturnUsing(function (string $key): int {
                if (str_starts_with($key, 'ACC-')) {
                    throw new RuntimeException('sequence failure');
                }

                return 1;
            });
        });

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'full_name' => 'Gagal Buat',
                'email' => 'gagal.buat@example.test',
                'pin' => '123456',
            ])
            ->assertServerError();

        $this->assertDatabaseMissing('customers', ['full_name' => 'Gagal Buat']);
        $this->assertDatabaseCount('accounts', 1);
    }
}
