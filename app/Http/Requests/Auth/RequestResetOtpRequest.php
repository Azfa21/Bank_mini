<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RequestResetOtpRequest extends FormRequest
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
            'account_type' => ['required', 'in:staff,nasabah'],
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_type.required' => 'Jenis akun wajib dipilih.',
            'account_type.in' => 'Jenis akun tidak valid.',
            'identifier.required' => 'Username atau nomor siswa wajib diisi.',
            'identifier.max' => 'Username atau nomor siswa terlalu panjang.',
        ];
    }
}
