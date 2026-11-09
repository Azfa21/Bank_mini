<?php

namespace App\Http\Requests\Teller;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'slip_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'amount' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'digits:6'],
            'withdrawal_booking_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.integer' => 'Nominal penarikan harus berupa angka tanpa titik atau koma.',
            'amount.min' => 'Nominal penarikan minimal Rp 1.',
            'pin.required' => 'PIN penabung wajib diisi.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
            'slip_number.max' => 'Nomor slip maksimal 30 karakter.',
        ];
    }
}
