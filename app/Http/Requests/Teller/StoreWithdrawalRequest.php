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
            'slip_number' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'digits:6'],
        ];
    }
}
