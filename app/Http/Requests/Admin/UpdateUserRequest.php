<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:50', 'unique:users,username,'.$userId],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'required', 'in:admin,teller,supervisor'],
            'status' => ['sometimes', 'required', 'in:active,blocked'],
        ];
    }
}
