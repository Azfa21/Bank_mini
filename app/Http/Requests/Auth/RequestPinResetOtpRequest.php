<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RequestPinResetOtpRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Nomor siswa wajib diisi.',
            'identifier.max' => 'Nomor siswa terlalu panjang.',
        ];
    }
}
