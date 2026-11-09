<?php

namespace App\Http\Requests\Mobile;

use App\Models\Saver;
use App\Services\WithdrawalBookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $firstAllowedDate = CarbonImmutable::now(WithdrawalBookingService::BUSINESS_TIMEZONE)
            ->startOfDay()
            ->addDay()
            ->toDateString();

        return [
            'withdrawal_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$firstAllowedDate],
            'amount' => ['required', 'integer', 'min:'.(WithdrawalBookingService::DAILY_LIMIT + 1)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'withdrawal_date.required' => 'Tanggal penarikan wajib dipilih.',
            'withdrawal_date.date_format' => 'Format tanggal penarikan tidak valid.',
            'withdrawal_date.after_or_equal' => 'Tanggal penarikan harus minimal satu hari setelah booking diajukan.',
            'amount.required' => 'Total rencana penarikan wajib diisi.',
            'amount.integer' => 'Total rencana penarikan harus berupa angka tanpa titik atau koma.',
            'amount.min' => 'Booking hanya diperlukan untuk total penarikan di atas Rp 1.000.000 per hari.',
            'notes.max' => 'Keterangan maksimal 500 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Saver|null $customer */
            $customer = $this->attributes->get('mobile_customer');
            $account = $customer?->account;

            if (! $account) {
                return;
            }

            $amount = (int) $this->input('amount');
            if ($amount <= WithdrawalBookingService::DAILY_LIMIT) {
                return;
            }

            $balance = (int) $account->balance;
            if ($amount > $balance) {
                $validator->errors()->add('amount', 'Saldo tidak mencukupi untuk mengajukan booking penarikan ini.');

                return;
            }

            $minimumBalance = $account->effectiveMinimumBalance();
            if (($balance - $amount) < $minimumBalance) {
                $validator->errors()->add(
                    'amount',
                    'Saldo setelah penarikan tidak boleh kurang dari saldo mengendap (Rp '.number_format($minimumBalance, 0, ',', '.').').'
                );
            }
        });
    }
}
