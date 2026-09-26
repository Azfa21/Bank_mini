<?php

namespace Tests\Feature\Mobile;

use App\Models\Transaction;
use App\Services\TransactionService;
use Tests\Feature\Teller\TellerTestCase;

abstract class MobileTestCase extends TellerTestCase
{
    protected function loginAndGetToken(): string
    {
        $response = $this->postJson(route('api.mobile.login'), [
            'student_number' => $this->nasabah->student_number,
            'pin' => '123456',
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }

    protected function withMobileToken(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    protected function deposit(int $amount, string $slip = 'SLIP-M-001'): Transaction
    {
        return app(TransactionService::class)->deposit($this->account, [
            'slip_number' => $slip,
            'slip_amount' => $amount,
            'physical_cash_amount' => $amount,
            'amount' => $amount,
        ], $this->teller);
    }
}
