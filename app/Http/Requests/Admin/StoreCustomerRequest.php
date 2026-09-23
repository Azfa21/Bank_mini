<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
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
            'class_name' => ['nullable', 'string', 'max:30'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'pin' => ['required', 'string', 'digits:6'],
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
