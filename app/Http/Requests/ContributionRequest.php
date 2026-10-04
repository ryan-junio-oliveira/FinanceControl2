<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ContributionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        $fid = Auth::user()?->group_id;

        return [
            'portfolio_id' => ['nullable', Rule::exists('portfolios', 'id')->where('group_id', $fid)],
            'asset_id' => ['nullable', Rule::exists('assets', 'id')->where('group_id', $fid), 'required_if:kind,rendimento'],
            'account_id' => ['required_if:kind,aporte', 'nullable', Rule::exists('accounts', 'id')->where('group_id', $fid)],
            'kind' => ['required', 'in:aporte,rendimento'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'portfolio_id.required' => 'Escolha a carteira.',            'asset_id.required_if' => 'Para rendimentos, escolha o ativo que rendeu.',
            'account_id.required_if' => 'Para aportes, escolha a conta de origem do dinheiro.',
            'amount.required' => 'Informe o valor.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'Informe a data.',
        ];
    }
}
