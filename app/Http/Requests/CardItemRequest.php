<?php

namespace App\Http\Requests;

class CardItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        return [
            'credit_card_id' => ['required', 'exists:credit_cards,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_card_id.required' => 'Escolha o cartão da compra.',
            'description.required' => 'Descreva a compra (ex.: Mercado Central).',
            'amount.required' => 'Informe o valor da compra.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'Informe a data da compra.',
            'user_id.required' => 'Escolha quem fez a compra.',
        ];
    }
}
