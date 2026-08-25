<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use App\Services\SequenceService;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Feature\Teller\TellerTestCase;

class CustomerCreationTest extends TellerTestCase
{
    private function academicYear(): string
    {
        return now()->format('Y').'/'.(now()->format('Y') + 1);
    }

    public function test_store_creates_customer_with_account_in_sequence(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'student_number' => '2026001',
                'full_name' => 'Budi Santoso',
                'class_name' => 'VII A',
                'phone_number' => '081234567777',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $customer = Customer::where('student_number', '2026001')->firstOrFail();
        $account = Account::where('customer_id', $customer->id)->firstOrFail();

        $expectedOrdinal = Account::where('academic_year', $this->academicYear())
            ->whereKeyNot($account->id)
            ->get()
            ->filter(fn (Account $existing) => str($existing->account_number)->contains('-'))
            ->map(fn (Account $existing) => (int) Str::afterLast($existing->account_number, '-'))
            ->max();

        $this->assertSame(
            $this->academicYear().'-'.str_pad((string) ($expectedOrdinal + 1), 3, '0', STR_PAD_LEFT),
            $account->account_number
        );
        $this->assertSame(0, $account->balance);
        $this->assertSame(10000, $account->minimum_balance);
        $this->assertSame('active', $account->status);
    }

    public function test_consecutive_creations_produce_distinct_numbers(): void
    {
        foreach ([['2026002', 'Citra Lestari'], ['2026003', 'Dewi Anggraini']] as [$nis, $name]) {
            $this->actingAs($this->admin, 'web')
                ->post(route('admin.customers.store'), [
                    'student_number' => $nis,
                    'full_name' => $name,
                    'pin' => '123456',
                ])
                ->assertRedirect(route('admin.customers.index'));
        }

        $numbers = Account::whereIn('customer_id', Customer::whereIn('student_number', ['2026002', '2026003'])->pluck('id'))
            ->pluck('account_number')
            ->all();

        $this->assertCount(2, $numbers);
        $this->assertCount(2, array_unique($numbers));

        $ordinals = array_map(fn (string $number) => (int) Str::afterLast($number, '-'), $numbers);
        sort($ordinals);
        $this->assertSame([$ordinals[0], $ordinals[0] + 1], $ordinals);
    }

    public function test_new_account_number_continues_after_legacy_higher_ordinal(): void
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
            'account_number' => $this->academicYear().'-009',
            'customer_id' => $legacyCustomer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => 10000,
            'academic_year' => $this->academicYear(),
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'student_number' => '2026010',
                'full_name' => 'Eko Prasetyo',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $newCustomer = Customer::where('student_number', '2026010')->firstOrFail();
        $newAccount = Account::where('customer_id', $newCustomer->id)->firstOrFail();

        $this->assertSame($this->academicYear().'-010', $newAccount->account_number);
    }

    public function test_customer_is_rolled_back_when_account_opening_fails(): void
    {
        $this->mock(SequenceService::class, function ($mock) {
            $mock->shouldReceive('next')->andThrow(new RuntimeException('sequence failure'));
        });

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'student_number' => '2026999',
                'full_name' => 'Gagal Buat',
                'pin' => '123456',
            ])
            ->assertServerError();

        $this->assertDatabaseMissing('customers', ['student_number' => '2026999']);
        $this->assertDatabaseCount('accounts', 1);
    }
}
