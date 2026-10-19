<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        /** @var Member|null $member */
        $member = $this->route('member');

        return [
            'nis' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('members', 'nis')->ignore($member?->nis, 'nis')],
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:30'],
            'graduation_year' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS tersebut sudah terdaftar.',
            'full_name.required' => 'Nama siswa wajib diisi.',
        ];
    }
}
