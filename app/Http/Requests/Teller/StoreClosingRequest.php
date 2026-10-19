<?php

namespace App\Http\Requests\Teller;

use App\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DailyReport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_date.required' => 'Tanggal laporan wajib diisi.',
            'report_date.date_format' => 'Tanggal laporan harus dalam format tahun-bulan-tanggal.',
        ];
    }
}
