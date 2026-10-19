<?php

namespace App\Http\Requests\Teller;

use App\Models\SystemConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMemberActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'teller';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && $this->input('email') !== null) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'slip_number' => ['nullable', 'string', 'max:30'],
            'pin' => ['required', 'digits:6'],
            // Tanpa email terdaftar, penabung tidak punya jalur pemulihan PIN
            // sama sekali: nomor siswa bisa saja ikut hilang bersama kartu.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:savers,email'],
            'class_name' => ['nullable', 'string', 'max:30'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $amount = (int) $this->input('amount');

            $minimum = SystemConfig::integer('min_deposit_to_activate', 10000);

            if ($amount < $minimum) {
                $validator->errors()->add('amount', 'Setoran aktivasi minimal Rp '.number_format($minimum, 0, ',', '.').'.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal setoran wajib diisi.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
            'email.required' => 'Email wajib diisi. Ini yang dipakai untuk mengirim kode pemulihan PIN.',
            'email.unique' => 'Email sudah digunakan penabung lain.',
        ];
    }
}
