<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'pin' => ['required', 'string', 'digits:6'],
        ];
    }
}
