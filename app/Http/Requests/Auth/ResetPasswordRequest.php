<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', $this->strongPassword()],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->strongPasswordMessages(), [
            'token.required' => 'Link de recuperação inválido. Solicite um novo.',
            'email.required' => 'Informe o e-mail da conta.',
            'email.email' => 'Esse e-mail não parece válido.',
        ]);
    }
}
