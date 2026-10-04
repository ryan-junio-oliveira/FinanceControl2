<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'manager_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'group_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', $this->strongPassword()],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->strongPasswordMessages(), [
            'manager_name.required' => 'Como podemos te chamar? Informe seu nome.',
            'email.required' => 'Informe um e-mail válido para acessar a conta.',
            'email.email' => 'Esse e-mail não parece válido. Confira e tente de novo.',
            'email.unique' => 'Este e-mail já tem conta no Prumo. Tente fazer login.',
            'group_name.required' => 'Dê um nome para a conta (ex.: Carlos ou Família Silva).',
            'terms.accepted' => 'Para criar a conta, aceite os Termos de Uso e a Política de Privacidade.',
        ]);
    }
}
