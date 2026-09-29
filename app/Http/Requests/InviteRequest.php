<?php

namespace App\Http\Requests;

class InviteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:co_admin,dependente,junior'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome de quem será convidado.',
            'email.required' => 'Informe o e-mail do convidado.',
            'email.email' => 'Esse e-mail não parece válido.',
            'role.required' => 'Escolha o papel do convidado na sua conta.',
            'role.in' => 'Papel inválido.',
        ];
    }
}
