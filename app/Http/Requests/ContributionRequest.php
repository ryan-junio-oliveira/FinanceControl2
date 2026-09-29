<?php

namespace App\Http\Requests;

class ContributionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        return [
            'portfolio_id' => ['required', 'exists:portfolios,id'],
            'account_id' => ['required_if:kind,aporte', 'nullable', 'exists:accounts,id'],
            'kind' => ['required', 'in:aporte,rendimento'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'portfolio_id.required' => 'Escolha a carteira.',
            'account_id.required_if' => 'Para aportes, escolha a conta de origem do dinheiro.',
            'amount.required' => 'Informe o valor.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'Informe a data.',
        ];
    }
}
