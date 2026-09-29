<?php

namespace App\Http\Requests;

class CreditCardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['credit_limit']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'closing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'holder_user_id' => ['nullable', 'exists:users,id'],
            'color' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome ao cartão (ex.: Nubank Ultravioleta).',
            'account_id.exists' => 'A conta vinculada não pertence à sua conta.',
            'credit_limit.required' => 'Informe o limite do cartão (pode ser 0).',
            'credit_limit.min' => 'O limite não pode ser negativo.',
            'closing_day.required' => 'Informe o dia de fechamento da fatura.',
            'closing_day.between' => 'O dia de fechamento deve estar entre 1 e 28.',
            'closing_day.min' => 'O dia de fechamento deve estar entre 1 e 28.',
            'closing_day.max' => 'O dia de fechamento deve estar entre 1 e 28.',
            'due_day.required' => 'Informe o dia de vencimento da fatura.',
            'due_day.min' => 'O dia de vencimento deve estar entre 1 e 28.',
            'due_day.max' => 'O dia de vencimento deve estar entre 1 e 28.',
            'holder_user_id.exists' => 'O titular selecionado não pertence à sua conta.',
        ];
    }
}
