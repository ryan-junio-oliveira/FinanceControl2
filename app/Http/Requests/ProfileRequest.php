<?php

namespace App\Http\Requests;

class ProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Esse e-mail não parece válido.',
            'email.unique' => 'Este e-mail já está em uso por outra pessoa.',
            'birthdate.date' => 'Essa data de nascimento não é válida.',
            'birthdate.before' => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}
