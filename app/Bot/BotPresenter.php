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

    /** Linha divisória padrão das mensagens. */
    public static function divider(): string
    {
        return '──────────────────';
    }

    /** Cabeçalho padrão: ✦ Título + divisória. */
    public static function header(string $title): string
    {
        return "✦ <b>{$title}</b>\n".self::divider();
    }

    /** Variação com seta: ▲ +1,25% · ▼ −0,50% · —. */
    public static function change(?float $value, string $suffix = '%'): string
    {
        if ($value === null) {
            return '—';
        }
        $num = number_format(abs($value), 2, ',', '.');
        if ($value > 0) {
            return "▲ +{$num}{$suffix}";
        }
        if ($value < 0) {
            return "▼ −{$num}{$suffix}";
        }

        return "0,00{$suffix}";
    }

    /** Barra de progresso em blocos: ▰▰▰▱▱ 45%. */
    public static function bar(float $pct, int $width = 10): string
    {
        $pct = max(0, min(100, $pct));
        $filled = (int) round($pct / 100 * $width);

        return str_repeat('▰', $filled).str_repeat('▱', $width - $filled).' '.(int) round($pct).'%';
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
        $resultado = $k['receitas_mes'] - $k['despesas_total_mes'];
        $pos = $resultado >= 0;
        $lines = [
            self::header('Dados financeiros'),
            self::mesLabel($mes),
            '',
            '💰 Receitas: <b>'.self::money($k['receitas_mes']).'</b>',
            '💸 Despesas: <b>'.self::money($k['despesas_total_mes']).'</b>',
            ($pos ? '✅' : '🔴').' Resultado: <b>'.self::money($resultado).'</b>',
            '',
            self::divider(),
            '🏦 Contas: <b>'.self::money($dados['saldoContas']).'</b>',
            '💳 Faturas: <b>'.self::money($dados['faturaAberto']).'</b>',
            '🧾 A pagar (15d): <b>'.self::money($dados['aPagar']['s15']['valor']).'</b> · '.$dados['aPagar']['s15']['qtd'].' contas',
        ];

        return implode("\n", $lines);
    }
}
