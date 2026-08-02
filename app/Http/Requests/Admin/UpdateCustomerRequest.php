<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id ?? $this->route('customer');

        return [
            'student_number' => ['sometimes', 'required', 'string', 'max:30', 'unique:customers,student_number,'.$customerId],
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pin' => ['sometimes', 'nullable', 'string', 'digits:6'],
            'status' => ['sometimes', 'required', 'in:active,blocked'],
        ];
    }
}
