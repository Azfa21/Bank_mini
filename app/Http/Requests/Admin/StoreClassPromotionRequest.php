<?php

namespace App\Http\Requests\Admin;

use App\Support\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:9'],
            'confirm_repeat' => ['nullable', 'boolean'],
            'rows' => ['required', 'array'],
            'rows.*.class' => ['required', 'string', 'max:30'],
            'rows.*.level' => ['nullable', 'string', Rule::in(ClassRoom::LEVELS)],
            'rows.*.major' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z]+$/'],
            'rows.*.rombel' => ['nullable', 'string', 'max:2', 'regex:/^\d{1,2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => 'Tidak ada kelas yang bisa diproses pada halaman ini.',
            'rows.*.class.required' => 'Nama kelas asal tidak terbaca. Muat ulang halaman ini.',
            'rows.*.level.in' => 'Tingkat tujuan hanya boleh X, XI, atau XII.',
            'rows.*.major.regex' => 'Jurusan tujuan hanya boleh huruf, misalnya RPL atau TKJ.',
            'rows.*.rombel.regex' => 'Nomor rombel hanya boleh angka, maksimal dua digit.',
        ];
    }
}
