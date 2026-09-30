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
     * Converte valor monetário BR para ponto decimal.
     *
     * Aceita "1.234,56", "7.000" (milhar sem decimal), "7000", "7.5" e
     * "R$ 1.234,56" — nunca interpreta milhar como decimal.
     */
    protected function normalizeMoney(array $fields): void
    {
        $data = [];
        foreach ($fields as $f) {
            if (! $this->has($f)) {
                continue;
            }
            $data[$f] = self::parseBrazilianDecimal($this->input($f));
        }
        $this->merge($data);
    }

    public static function parseBrazilianDecimal(mixed $raw): ?string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return null;
        }
        // Remove símbolo de moeda e espaços: "R$ 1.234,56" → "1.234,56".
        $v = str_replace(['R$', 'r$', '$'], '', $v);
        $v = preg_replace('/\s+/', '', $v) ?? '';
        if ($v === '' || $v === '-' || $v === '+' || $v === ',') {
            return null;
        }
        if (str_contains($v, ',')) {
            // Formato BR: pontos são milhar, vírgula é decimal.
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (preg_match('/^[+-]?\d{1,3}(?:\.\d{3})+$/', $v)) {
            // Só pontos em grupos de milhar: "7.000" → "7000" (nunca 7,0).
            $v = str_replace('.', '', $v);
        }

        return $v;
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
