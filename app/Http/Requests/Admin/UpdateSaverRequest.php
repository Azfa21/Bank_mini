<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $saverId = $this->route('saver')?->id;

        return [
            'student_number' => ['sometimes', 'required', 'string', 'max:30', 'unique:savers,student_number,'.$saverId],
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:savers,email,'.$saverId],
            'pin' => ['sometimes', 'nullable', 'string', 'digits:6'],
            // Tanpa `sometimes`: kalau kolomnya tidak dikirim, aturan wajib
            // tetap harus dievaluasi saat PIN baru diisi.
            'reset_reason' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->filled('pin'))],
            'status' => ['sometimes', 'required', 'in:active,blocked'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk pemulihan password.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan penabung lain.',
            'reset_reason.required' => 'Tulis alasan reset PIN. Catatan ini masuk ke log audit.',
        ];
    }
}
