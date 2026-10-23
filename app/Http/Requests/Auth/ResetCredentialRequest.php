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
        if ($this->has('identifier')) {
            $this->merge(['identifier' => trim((string) $this->input('identifier'))]);
        }
    }

    public function rules(): array
    {
        // Pegawai maupun penabung sama-sama memulihkan password login; PIN
        // transaksi tidak pernah ikut alur OTP ini.
        return [
            'account_type' => ['required', 'in:staff,nasabah'],
            'identifier' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_type.required' => 'Jenis akun wajib dipilih.',
            'account_type.in' => 'Jenis akun tidak valid.',
            'identifier.required' => 'Username atau nomor siswa wajib diisi.',
            'identifier.max' => 'Username atau nomor siswa terlalu panjang.',
            'otp.required' => 'Kode verifikasi wajib diisi.',
            'otp.digits' => 'Kode verifikasi harus 6 digit angka.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
