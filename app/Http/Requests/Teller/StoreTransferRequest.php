<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    public function rules(): array
    {
        return [
            'recipient_account_number' => ['required', 'string', 'exists:accounts,account_number'],
            'amount' => ['required', 'integer', 'min:1'],
            'pin' => ['required', 'digits:6'],
            'slip_number' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sourceAccount = $this->route('account');
            $recipientNumber = $this->input('recipient_account_number');

            if ($sourceAccount && $sourceAccount->account_number === $recipientNumber) {
                $validator->errors()->add('recipient_account_number', 'Rekening tujuan tidak boleh sama dengan rekening pengirim.');
            }
        });
    }
}
