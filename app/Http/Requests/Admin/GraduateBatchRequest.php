<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GraduateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'graduation_year' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'graduation_year.required' => 'Pilih angkatan keluar yang ingin diproses.',
        ];
    }
}
