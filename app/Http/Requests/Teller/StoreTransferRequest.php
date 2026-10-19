<?php

namespace App\Http\Requests\Teller;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
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

    public function messages(): array
    {
        return [
            'recipient_account_number.required' => 'Nomor rekening tujuan wajib diisi.',
            'recipient_account_number.exists' => 'Nomor rekening tujuan tidak ditemukan. Periksa lagi angka pada buku tabungan.',
            'amount.required' => 'Nominal transfer wajib diisi.',
            'amount.integer' => 'Nominal transfer harus berupa angka tanpa titik atau koma.',
            'amount.min' => 'Nominal transfer minimal Rp 1.',
            'pin.required' => 'PIN pengirim wajib diisi.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
            'slip_number.max' => 'Nomor slip maksimal 30 karakter.',
            'notes.max' => 'Keterangan maksimal 255 karakter.',
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
