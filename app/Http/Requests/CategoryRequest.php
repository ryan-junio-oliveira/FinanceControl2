<?php

namespace App\Http\Requests;

class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['monthly_cap']);
    }

    public function rules(): array
    {
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:100'],
                'monthly_cap' => ['nullable', 'numeric', 'min:0'],
                'archived' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:despesa,receita'],
            'icon' => ['nullable', 'string', 'max:100'],
            'monthly_cap' => ['nullable', 'numeric', 'min:0'],
            'subcategories' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome à categoria (ex.: Alimentação).',
            'type.required' => 'Escolha se é categoria de despesa ou receita.',
            'type.in' => 'Tipo inválido. Escolha despesa ou receita.',
            'monthly_cap.numeric' => 'O teto mensal deve ser um número.',
            'monthly_cap.min' => 'O teto mensal não pode ser negativo.',
        ];
    }
}
