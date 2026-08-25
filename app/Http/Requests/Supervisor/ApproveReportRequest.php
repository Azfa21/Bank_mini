<?php

namespace App\Http\Requests\Supervisor;

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
}
