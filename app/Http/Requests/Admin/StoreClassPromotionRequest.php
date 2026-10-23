<?php

namespace App\Http\Requests\Admin;

use App\Services\ClassPromotionService;
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
            'rows.*.action' => ['required', 'string', Rule::in([ClassPromotionService::ACTION_PROMOTE, ClassPromotionService::ACTION_RETAIN])],
            // Kunci "member:<nis>" atau "saver:<nomor siswa>" untuk siswa yang tinggal kelas.
            'rows.*.retain' => ['nullable', 'array'],
            'rows.*.retain.*' => ['required', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => 'Tidak ada kelas yang bisa diproses pada halaman ini.',
            'rows.*.class.required' => 'Nama kelas asal tidak terbaca. Muat ulang halaman ini.',
            'rows.*.action.required' => 'Pilih tindakan tiap kelas: naik kelas atau tinggal kelas.',
            'rows.*.action.in' => 'Tindakan tiap kelas hanya boleh naik kelas atau tinggal kelas.',
            'rows.*.retain.array' => 'Daftar siswa yang tinggal kelas tidak terbaca. Muat ulang halaman ini.',
            'rows.*.retain.*.required' => 'Pilihan siswa yang tinggal kelas tidak terbaca. Muat ulang halaman ini.',
            'rows.*.retain.*.max' => 'Pilihan siswa yang tinggal kelas tidak terbaca. Muat ulang halaman ini.',
        ];
    }
}
