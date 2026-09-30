<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    public function rules(): array
    {
        $fid = Auth::user()?->family_id;

        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'occurred_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:occurred_on'],
            'status' => ['required', 'in:pago,pendente,agendado'],
            'user_id' => ['required', Rule::exists('users', 'id')->where('family_id', $fid)],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('family_id', $fid)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('family_id', $fid)],
            'is_fixed' => ['sometimes', 'boolean'],
            'installments_total' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:48'],
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
            'due_on.after_or_equal' => 'O vencimento não pode ser anterior à data do lançamento.',
            'status.required' => 'Escolha a situação do lançamento.',
            'status.in' => 'Situação inválida. Escolha entre pago, pendente ou agendado.',
            'user_id.required' => 'Escolha o membro responsável.',
            'user_id.exists' => 'O membro selecionado não pertence à família.',
            'account_id.exists' => 'A conta selecionada não pertence à família.',
            'category_id.exists' => 'A categoria selecionada não pertence à família.',
        ];
    }
}
