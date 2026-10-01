<?php

namespace App\Http\Requests;

class MemberRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:co_admin,dependente,junior'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Escolha o novo papel do membro.',
            'role.in' => 'Papel inválido.',
        ];
    }
}
