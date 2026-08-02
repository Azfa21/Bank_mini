<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    public function rules(): array
    {
        return [
            'slip_number' => ['required', 'string', 'max:30'],
            'slip_amount' => ['required', 'integer', 'min:1'],
            'physical_cash_amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $validator->errors()->addIf(
                    $this->integer('slip_amount') !== $this->integer('physical_cash_amount'),
                    'physical_cash_amount',
                    'Nominal uang fisik harus sama dengan nominal slip.'
                );
            }
        });
    }
}
