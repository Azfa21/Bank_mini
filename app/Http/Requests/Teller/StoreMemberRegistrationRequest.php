<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    public function rules(): array
    {
        return [
            'nis' => ['nullable', 'string', 'max:30', 'unique:members,nis'],
            'full_name' => ['required', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'graduation_year' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS tersebut sudah terdaftar sebagai anggota.',
            'full_name.required' => 'Nama siswa wajib diisi.',
        ];
    }
}
