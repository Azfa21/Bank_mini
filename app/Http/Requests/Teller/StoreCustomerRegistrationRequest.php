<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'class_name' => ['required', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'pin' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk pemulihan PIN.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan nasabah lain.',
        ];
    }
}
