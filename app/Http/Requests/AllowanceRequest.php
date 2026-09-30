<?php

namespace App\Http\Requests;

class AllowanceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:mensal,semanal'],
            'payday' => ['required', 'integer', 'min:0', 'max:28'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Escolha a pessoa da mesada.',
            'amount.required' => 'Informe o valor da mesada.',
            'amount.min' => 'O valor da mesada não pode ser negativo.',
            'frequency.required' => 'Escolha a frequência (mensal ou semanal).',
            'payday.required' => 'Informe o dia do repasse.',
            'payday.between' => 'O dia do repasse deve estar entre 0 e 28.',
            'payday.min' => 'O dia do repasse deve estar entre 0 e 28.',
            'payday.max' => 'O dia do repasse deve estar entre 0 e 28.',
        ];
    }
}
