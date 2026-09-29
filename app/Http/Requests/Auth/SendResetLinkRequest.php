<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class SendResetLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail cadastrado na conta familiar.',
            'email.email' => 'Esse e-mail não parece válido. Confira e tente de novo.',
        ];
    }
}
