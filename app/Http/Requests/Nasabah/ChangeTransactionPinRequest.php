<?php

namespace App\Http\Requests\Nasabah;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTransactionPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('nasabah')->check();
    }

    protected function prepareForValidation(): void
    {
        foreach (['current_pin', 'pin', 'pin_confirmation'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'current_pin' => ['required', 'string', 'digits:6'],
            'pin' => ['required', 'string', 'digits:6', 'different:current_pin', 'confirmed'],
            'pin_confirmation' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_pin.required' => 'PIN saat ini wajib diisi.',
            'current_pin.digits' => 'PIN saat ini harus tepat 6 digit angka.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'pin.different' => 'PIN baru tidak boleh sama dengan PIN saat ini.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
            'pin_confirmation.required' => 'Konfirmasi PIN baru wajib diisi.',
            'pin_confirmation.digits' => 'Konfirmasi PIN baru harus tepat 6 digit angka.',
        ];
    }
}
