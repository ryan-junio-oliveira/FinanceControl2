<?php

namespace App\Http\Requests;

class SettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['approval_threshold', 'privacy_hide_under']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'in:BRL,USD,EUR'],
            'timezone' => ['required', 'string', 'max:100'],
            'closing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'approval_threshold' => ['required', 'numeric', 'min:0'],
            'privacy_hide_under' => ['required', 'numeric', 'min:0'],
            'consolidate_dependent_yield' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A família precisa de um nome.',
            'currency.in' => 'Moeda inválida. Escolha entre Real, Dólar ou Euro.',
            'closing_day.min' => 'O dia de fechamento deve estar entre 1 e 28.',
            'closing_day.max' => 'O dia de fechamento deve estar entre 1 e 28.',
            'approval_threshold.min' => 'O limite de aprovação não pode ser negativo.',
            'privacy_hide_under.min' => 'O valor de privacidade não pode ser negativo.',
        ];
    }
}
