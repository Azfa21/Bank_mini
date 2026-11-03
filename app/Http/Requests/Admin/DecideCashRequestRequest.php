<?php

namespace App\Http\Requests\Admin;

use App\Models\CashRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideCashRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cashRequest = $this->route('cashRequest');

        if (! $cashRequest instanceof CashRequest) {
            return false;
        }

        $ability = $this->routeIs('admin.cash-requests.approve') ? 'approve' : 'reject';

        return $this->user()?->can($ability, $cashRequest) === true;
    }

    public function rules(): array
    {
        return [
            'decision_notes' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf($this->routeIs('admin.cash-requests.reject')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'decision_notes.required' => 'Alasan penolakan wajib diisi agar Teller tahu penyebabnya.',
            'decision_notes.max' => 'Catatan keputusan maksimal 500 karakter.',
        ];
    }
}
