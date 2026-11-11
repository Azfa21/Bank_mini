<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPinCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('identifier')) {
            $this->merge(['identifier' => trim((string) $this->input('identifier'))]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'string', 'digits:6'],
            'pin' => ['required', 'string', 'digits:6', 'confirmed'],
            'pin_confirmation' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Nomor siswa wajib diisi.',
            'identifier.max' => 'Nomor siswa terlalu panjang.',
            'otp.required' => 'Kode verifikasi wajib diisi.',
            'otp.digits' => 'Kode verifikasi harus 6 digit angka.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
            'pin_confirmation.required' => 'Konfirmasi PIN baru wajib diisi.',
            'pin_confirmation.digits' => 'Konfirmasi PIN baru harus tepat 6 digit angka.',
        ];
    }
}
