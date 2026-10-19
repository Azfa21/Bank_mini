<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_notes' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_notes.required' => 'Catatan pemeriksaan wajib diisi supaya jejak validasi jelas.',
            'approval_notes.max' => 'Catatan pemeriksaan maksimal 500 karakter.',
        ];
    }
}
