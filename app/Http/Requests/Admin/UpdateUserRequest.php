<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:50', 'unique:users,username,'.$userId],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            // Reset password tanpa alasan tertulis tidak bisa ditelusuri
            // nanti: audit hanya akan menyimpan siapa, bukan atas dasar apa.
            'reset_reason' => ['sometimes', 'nullable', 'string', 'max:255', Rule::requiredIf($this->filled('password'))],
            'role' => ['sometimes', 'required', 'in:admin,teller'],
            'status' => ['sometimes', 'required', 'in:active,blocked'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk pemulihan kredensial.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'reset_reason.required' => 'Tulis alasan reset password. Catatan ini masuk ke log audit.',
        ];
    }
}
