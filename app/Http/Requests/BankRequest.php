<?php

namespace App\Http\Requests;

class BankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bank' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank.required' => 'Informe o nome do banco (ex.: Itaú).',
        ];
    }
}
