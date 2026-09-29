<?php

namespace App\Http\Requests;

class ProfilePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', $this->strongPassword()],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->strongPasswordMessages(), [
            'current_password.required' => 'Informe sua senha atual.',
            'current_password.current_password' => 'Sua senha atual não confere. Tente de novo.',
        ]);
    }
}
