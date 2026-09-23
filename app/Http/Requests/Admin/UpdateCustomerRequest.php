<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
        $customerId = $this->route('customer')?->id;

        return [
            'student_number' => ['sometimes', 'required', 'string', 'max:30', 'unique:customers,student_number,'.$customerId],
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:customers,email,'.$customerId],
            'pin' => ['sometimes', 'nullable', 'string', 'digits:6'],
            'status' => ['sometimes', 'required', 'in:active,blocked'],
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
