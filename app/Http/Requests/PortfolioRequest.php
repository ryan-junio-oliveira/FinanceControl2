<?php

namespace App\Http\Requests;

class PortfolioRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['target_amount']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'kind' => ['required', 'in:reserva,estudos,futuro,livre'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome à carteira (ex.: Reserva de Emergência).',
            'kind.required' => 'Escolha o tipo da carteira.',
            'kind.in' => 'Tipo de carteira inválido.',
            'target_amount.min' => 'A meta não pode ser negativa.',
            'deadline.after' => 'O prazo deve ser uma data futura.',
        ];
    }
}
