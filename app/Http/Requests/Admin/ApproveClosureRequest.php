<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->route('closure')) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision_notes.max' => 'Catatan keputusan maksimal 500 karakter.',
        ];
    }
}
