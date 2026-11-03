<?php

namespace App\Http\Requests\Teller;

use App\Models\CashRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CashRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal permintaan kas wajib diisi.',
            'amount.integer' => 'Nominal harus berupa angka rupiah tanpa tanda baca.',
            'amount.min' => 'Nominal harus lebih dari nol.',
            'amount.max' => 'Nominal maksimal Rp 10.000.000.',
        ];
    }
}
