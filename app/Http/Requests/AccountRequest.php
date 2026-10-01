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
            'bank_id' => ['required', 'exists:banks,id'],
            'kind' => ['required', 'in:corrente,poupanca,digital,investimento,carteira'],
            'initial_balance' => ['required', 'numeric'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome à conta (ex.: Itaú Conjunta).',
            'bank_id.required' => 'Escolha o banco da conta.',
            'bank_id.exists' => 'Banco inválido. Escolha um banco do catálogo.',
            'kind.required' => 'Escolha o tipo da conta.',
            'kind.in' => 'Tipo de conta inválido.',
            'initial_balance.required' => 'Informe o saldo inicial (pode ser 0).',
            'initial_balance.numeric' => 'O saldo inicial deve ser um número.',
        ];
    }
}
