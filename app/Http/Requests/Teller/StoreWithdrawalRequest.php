<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    public function rules(): array
    {
        return [
            'slip_number' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'digits:6'],
        ];
    }
}
