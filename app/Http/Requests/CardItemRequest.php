<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CardItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        $fid = Auth::user()?->family_id;

        return [
            'credit_card_id' => ['required', Rule::exists('credit_cards', 'id')->where('family_id', $fid)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'user_id' => ['required', Rule::exists('users', 'id')->where('family_id', $fid)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('family_id', $fid)],
            'kind' => ['sometimes', 'in:compra,estorno'],
            'installments_total' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:48'],
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
