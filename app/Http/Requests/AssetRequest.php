<?php

namespace App\Http\Requests;

class AssetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['current_value']);
    }

    public function rules(): array
    {
        return [
            'portfolio_id' => ['required', 'exists:portfolios,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'holder' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'in:renda_fixa,fii,acao,etf,previdencia'],
            'current_value' => ['required', 'numeric', 'min:0'],
            'profitability' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'portfolio_id.required' => 'Escolha a carteira do ativo.',
            'code.required' => 'Informe o código do ativo (ex.: HGLG11).',
            'name.required' => 'Informe o nome do ativo.',
            'kind.required' => 'Escolha a classe do ativo.',
            'kind.in' => 'Classe de ativo inválida.',
            'current_value.required' => 'Informe o valor atual do ativo.',
            'current_value.min' => 'O valor atual não pode ser negativo.',
        ];
    }
}
