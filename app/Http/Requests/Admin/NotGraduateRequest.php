<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NotGraduateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'graduation_year' => ['required', 'string', 'max:10'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'graduation_year.required' => 'Muat ulang halaman lalu coba lagi.',
            'reason.max' => 'Alasan maksimal 500 karakter.',
        ];
    }
}
