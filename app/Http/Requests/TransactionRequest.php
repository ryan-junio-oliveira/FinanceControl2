<?php

namespace App\Http\Requests;

class TransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date'],
            'status' => ['required', 'in:pago,pendente,agendado'],
            'user_id' => ['required', 'exists:users,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_fixed' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Dê uma descrição ao lançamento (ex.: Mercado Central).',
            'amount.required' => 'Informe o valor do lançamento.',
            'amount.numeric' => 'O valor deve ser um número (ex.: 150,00).',
            'amount.min' => 'O valor deve ser maior que zero.',
            'occurred_on.required' => 'Informe a data do lançamento.',
            'occurred_on.date' => 'Essa data não é válida.',
            'due_on.date' => 'Esse vencimento não é válido.',
            'status.required' => 'Escolha a situação do lançamento.',
            'status.in' => 'Situação inválida. Escolha entre pago, pendente ou agendado.',
            'user_id.required' => 'Escolha o membro responsável.',
            'user_id.exists' => 'O membro selecionado não pertence à família.',
            'account_id.exists' => 'A conta selecionada não pertence à família.',
            'category_id.exists' => 'A categoria selecionada não pertence à família.',
        ];
    }
}
