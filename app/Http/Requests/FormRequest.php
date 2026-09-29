<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as BaseRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Base das Form Requests do FinFamília.
 *
 * Centraliza a regra de senha forte e o redirecionamento de erros
 * (volta para a página anterior mantendo os dados digitados).
 */
abstract class FormRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Regra padrão de senha forte do FinFamília. */
    protected function strongPassword(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }

    /**
     * Converte valor monetário BR ("1.234,56" ou "150,00") para ponto decimal.
     */
    protected function normalizeMoney(array $fields): void
    {
        $data = [];
        foreach ($fields as $f) {
            if (! $this->has($f)) {
                continue;
            }
            $v = trim((string) $this->input($f));
            if (preg_match('/,\d{1,2}$/', $v)) {
                $v = str_replace('.', '', $v);
                $v = str_replace(',', '.', $v);
            }
            $data[$f] = $v === '' ? null : $v;
        }
        $this->merge($data);
    }

    /**
     * Mensagens amigáveis da senha forte.
     *
     * As sub-regras do objeto Password falham como `password.mixed`,
     * `password.symbols` etc. — por isso a chave composta.
     */
    protected function strongPasswordMessages(string $field = 'password', string $label = 'senha'): array
    {
        return [
            "{$field}.required" => "A {$label} é obrigatória.",
            "{$field}.min" => "A {$label} deve ter ao menos :min caracteres.",
            "{$field}.confirmed" => 'A confirmação não coincide. Digite a mesma senha nos dois campos.',
            "{$field}.password.mixed" => "A {$label} deve conter ao menos uma letra maiúscula e uma minúscula.",
            "{$field}.password.letters" => "A {$label} deve conter ao menos uma letra.",
            "{$field}.password.numbers" => "A {$label} deve conter ao menos um número.",
            "{$field}.password.symbols" => 'A senha deve conter ao menos um símbolo (ex.: ! @ # $ %).',
            "{$field}.password.uncompromised" => 'Essa senha já vazou em algum incidente de segurança. Escolha outra.',
        ];
    }
}
