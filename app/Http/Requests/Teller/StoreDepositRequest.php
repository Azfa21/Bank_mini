<?php

namespace App\Http\Requests\Teller;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'slip_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'slip_amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'slip_amount.required' => 'Nominal pada slip wajib diisi.',
            'slip_amount.integer' => 'Nominal pada slip harus berupa angka tanpa titik atau koma.',
            'slip_amount.min' => 'Nominal pada slip minimal Rp 1.',
            'slip_number.max' => 'Nomor slip maksimal 30 karakter.',
        ];
    }
}
