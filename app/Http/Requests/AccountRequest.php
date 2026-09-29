<?php

namespace App\Http\Requests;

class AccountRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['initial_balance']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:50'],
            'number' => ['nullable', 'string', 'max:50'],
            'kind' => ['required', 'in:corrente,poupanca,digital,investimento,carteira'],
            'initial_balance' => ['required', 'numeric'],
            'active' => ['sometimes', 'boolean'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome à conta (ex.: Itaú Conjunta).',
            'kind.required' => 'Escolha o tipo da conta.',
            'kind.in' => 'Tipo de conta inválido.',
            'initial_balance.required' => 'Informe o saldo inicial (pode ser 0).',
            'initial_balance.numeric' => 'O saldo inicial deve ser um número.',
            'color.regex' => 'Escolha uma cor válida para a conta.',
        ];
    }
}
