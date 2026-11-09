<?php

namespace App\Http\Requests\Mobile;

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
}
