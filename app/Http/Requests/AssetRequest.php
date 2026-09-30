<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['current_value', 'yield_percent']);
    }

    public function rules(): array
    {
        $fid = Auth::user()?->family_id;

        return [
            'portfolio_id' => ['nullable', Rule::exists('portfolios', 'id')->where('family_id', $fid)],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'holder' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'in:renda_fixa,fii,acao,etf,previdencia'],
            'current_value' => ['required', 'numeric', 'min:0'],
            'yield_percent' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'yield_base' => ['nullable', 'in:cdi,selic,ipca,prefixado'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do ativo (ex.: CDB Inter).',
            'name.required' => 'Informe o nome do ativo.',
            'kind.required' => 'Escolha a classe do ativo.',
            'kind.in' => 'Classe de ativo inválida.',
            'current_value.required' => 'Informe o valor atual do ativo.',
            'current_value.min' => 'O valor atual não pode ser negativo.',
            'yield_percent.min' => 'A rentabilidade não pode ser negativa.',
        ];
    }
}
