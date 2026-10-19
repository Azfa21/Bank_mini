<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', $this->route('report')) ?? false;
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
            'approval_notes.required' => 'Alasan penolakan wajib diisi agar Teller tahu yang harus diperbaiki.',
            'approval_notes.max' => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}
