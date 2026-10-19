<?php

namespace App\Http\Requests\Admin;

use App\Support\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveClassMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'saver_ids' => ['required', 'array', 'min:1'],
            'saver_ids.*' => ['integer', Rule::exists('savers', 'id')->whereNull('deleted_at')],
            'level' => ['required', Rule::in(ClassRoom::LEVELS)],
            'major' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z]+$/'],
            'rombel' => ['nullable', 'string', 'max:2', 'regex:/^\d{1,2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'saver_ids.required' => 'Pilih dulu penabung yang ingin dipindahkan.',
            'saver_ids.min' => 'Pilih dulu penabung yang ingin dipindahkan.',
            'level.required' => 'Pilih tingkat kelas tujuan.',
            'major.required' => 'Pilih jurusan kelas tujuan.',
            'major.regex' => 'Jurusan tujuan hanya boleh huruf, misalnya RPL atau TKJ.',
            'rombel.regex' => 'Nomor rombel hanya boleh angka, maksimal dua digit.',
        ];
    }
}
