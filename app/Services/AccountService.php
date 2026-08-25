<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Support\Str;

class AccountService
{
    public const DEFAULT_MINIMUM_BALANCE = 10000;

    public function __construct(
        protected SequenceService $sequences,
    ) {}

    public function openFor(Customer $customer, int $minimumBalance = self::DEFAULT_MINIMUM_BALANCE): Account
    {
        $academicYear = now()->format('Y').'/'.(now()->format('Y') + 1);

        $floor = (int) Account::query()
            ->where('academic_year', $academicYear)
            ->get()
            ->map(fn (Account $account) => (int) Str::afterLast($account->account_number, '-'))
            ->max();

        $ordinal = $this->sequences->next(
            'ACC-'.str_replace('/', '', $academicYear),
            $floor,
        );

        return Account::create([
            'account_number' => $academicYear.'-'.str_pad((string) $ordinal, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'balance' => 0,
            'opening_balance' => 0,
            'minimum_balance' => $minimumBalance,
            'academic_year' => $academicYear,
            'status' => 'active',
            'opened_at' => now(),
        ]);
    }
}
