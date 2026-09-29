<?php

namespace App\Http\Requests;

class TransferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_account_id.required' => 'Escolha a conta de origem.',
            'to_account_id.required' => 'Escolha a conta de destino.',
            'to_account_id.different' => 'Origem e destino devem ser contas diferentes.',
            'amount.required' => 'Informe o valor da transferência.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'Informe a data da transferência.',
            'user_id.required' => 'Escolha o responsável pela transferência.',
        ];
    }
}
