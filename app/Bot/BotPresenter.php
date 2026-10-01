<?php

namespace App\Bot;

use App\Http\Requests\FormRequest;
use App\Support\Fin;
use Carbon\Carbon;

/** Formata dados do domínio como texto de chat (espelho do frontend). */
final class BotPresenter
{
    public static function money(float $value): string
    {
        return Fin::money($value);
    }

    public static function mesLabel(string $mes): string
    {
        return ucfirst(Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F/Y'));
    }

    /** "150,50" / "150.50" / "1.234,56" → float ou null. */
    public static function parseAmount(string $text): ?float
    {
        $parsed = FormRequest::parseBrazilianDecimal($text);
        if ($parsed === null || ! is_numeric($parsed)) {
            return null;
        }
        $value = (float) $parsed;

        return $value > 0 ? round($value, 2) : null;
    }

    /** "hoje", "25/10" ou "25/10/2026" → Y-m-d ou null. */
    public static function parseDate(string $text, ?string $fallback = null): ?string
    {
        $text = mb_strtolower(trim($text));
        if (in_array($text, ['hoje', 'hj', 'agora'], true)) {
            return $fallback ?? date('Y-m-d');
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})(?:/(\d{2,4}))?$#', $text, $m)) {
            $year = isset($m[3]) ? (int) $m[3] : (int) date('Y');
            if ($year < 100) {
                $year += 2000;
            }
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
            }
        }

        return null;
    }

    public static function dashboardText(array $dados, string $mes): string
    {
        $k = $dados['kpi'];
        $lines = [
            '📊 <b>Dados financeiros — '.self::mesLabel($mes).'</b>',
            '',
            '💰 Receitas: '.self::money($k['receitas']['atual']),
            '💸 Despesas: '.self::money($k['despesas']['atual']),
            ($k['resultado']['atual'] >= 0 ? '✅' : '🔴').' Resultado: '.self::money($k['resultado']['atual']),
            '',
            '🏦 Saldo em contas: '.self::money($dados['saldoContas']),
            '💳 Faturas em aberto: '.self::money($dados['faturaAberto']),
            '🧾 A pagar (30d): '.self::money($dados['aPagar']['s30']['valor']).' ('.$dados['aPagar']['s30']['qtd'].')',
        ];

        return implode("\n", $lines);
    }
}
