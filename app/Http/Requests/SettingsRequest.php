<?php

namespace App\Http\Requests;

class SettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'in:BRL,USD,EUR'],
            'timezone' => ['required', 'string', 'max:100'],
            'consolidate_dependent_yield' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A grupo precisa de um nome.',
            'currency.in' => 'Moeda inválida. Escolha entre Real, Dólar ou Euro.',
        ];
    }
}
