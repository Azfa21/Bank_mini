<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $isStaff = $this->input('account_type') === 'staff';

        return [
            'account_type' => ['required', 'in:staff,nasabah'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'otp' => ['required', 'string', 'digits:6'],
            'password' => $isStaff ? ['required', 'string', 'min:8', 'confirmed'] : ['nullable'],
            'pin' => $isStaff ? ['nullable'] : ['required', 'string', 'digits:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_type.required' => 'Jenis akun wajib dipilih.',
            'account_type.in' => 'Jenis akun tidak valid.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'otp.required' => 'Kode verifikasi wajib diisi.',
            'otp.digits' => 'Kode verifikasi harus 6 digit angka.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
        ];
    }
}
