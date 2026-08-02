<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class RejectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'supervisor';
    }

    public function rules(): array
    {
        return [
            'approval_notes' => ['required', 'string', 'max:500'],
        ];
    }
}
