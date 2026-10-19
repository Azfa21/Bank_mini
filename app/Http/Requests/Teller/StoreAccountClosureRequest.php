<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'digits:6'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'PIN penabung wajib diisi untuk mengotorisasi pencairan saldo.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
        ];
    }
}
