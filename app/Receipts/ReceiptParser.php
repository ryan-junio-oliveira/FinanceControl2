<?php

namespace App\Receipts;

use App\Support\CategoryKeywords;

/**
 * Interpreta o texto de um comprovante (Pix, TED, boleto, cartão).
 *
 * Retorna um rascunho com confiança: o que o sistema não entendeu
 * com segurança, o bot pergunta ao usuário.
 *
 * @return array{bank: ?string, amount: ?float, date: ?string, description: string, channel: string, direction: ?string, type: ?string, confidence: string, missing: array<int, string>}
 */
final class ReceiptParser
{
    private const BANKS = [
        'Nubank' => ['nubank', 'nu pagamentos'],
        'Itaú' => ['itau', 'itaú', 'itau unibanco'],
        'Inter' => ['banco inter', ' inter '],
        'Bradesco' => ['bradesco'],
        'Santander' => ['santander'],
        'Banco do Brasil' => ['banco do brasil'],
        'Caixa Econômica Federal' => ['caixa economica', 'caixa econômica', 'cef '],
        'C6 Bank' => ['c6 bank', 'c6bank'],
        'BTG Pactual' => ['btg'],
        'XP Investimentos' => [' xp ', 'xp invest'],
        'Mercado Pago' => ['mercado pago'],
        'PicPay' => ['picpay'],
        'PagBank' => ['pagbank', 'pagseguro'],
        'Safra' => ['safra'],
        'Banrisul' => ['banrisul'],
    ];

    public static function parse(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: [])));
        $norm = CategoryKeywords::normalize(implode("\n", $lines));

        $channel = self::channel($norm);
        $direction = self::direction($norm, $channel);
        $amount = self::amount($text);
        $date = self::date($text);
        $bank = self::bank($norm);
        $description = self::description($lines, $channel);

        $missing = [];
        if ($amount === null) {
            $missing[] = 'amount';
        }
        if ($date === null) {
            $missing[] = 'date';
        }
        if ($direction === null) {
            $missing[] = 'kind';
        }

        return [
            'bank' => $bank,
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
            'channel' => $channel,
            'direction' => $direction,
            'type' => $direction === 'in' ? 'receita' : ($direction === 'out' ? 'despesa' : null),
            'confidence' => $missing === [] ? 'high' : 'low',
            'missing' => $missing,
        ];
    }

    private static function channel(string $norm): string
    {
        if (str_contains($norm, 'pix')) {
            return 'pix';
        }
        if (str_contains($norm, 'boleto') || str_contains($norm, 'linha digitavel') || str_contains($norm, 'codigo de barras')) {
            return 'boleto';
        }
        if (str_contains($norm, 'cartao') || str_contains($norm, 'credito') || str_contains($norm, 'fatura')) {
            return 'cartao';
        }
        if (str_contains($norm, 'ted') || str_contains($norm, 'doc ') || str_contains($norm, 'transferencia')) {
            return 'ted';
        }

        return 'outro';
    }

    /** in = entrou dinheiro (receita) · out = saiu (despesa). */
    private static function direction(string $norm, string $channel): ?string
    {
        $in = ['recebido', 'recebida', 'recebimento', 'creditado', 'creditada', 'entrada', 'receber'];
        $out = ['enviado', 'enviada', 'pagamento', 'pago', 'paga', 'debitado', 'debitada', 'saida', 'transferido'];
        // "Pix enviado a X" vs "Pix recebido de X" — o verbo manda.
        foreach ($in as $w) {
            if (str_contains($norm, $w)) {
                return 'in';
            }
        }
        foreach ($out as $w) {
            if (str_contains($norm, $w)) {
                return 'out';
            }
        }
        // Boleto quase sempre é pagamento.
        if ($channel === 'boleto') {
            return 'out';
        }

        return null;
    }

    /** Maior valor em R$ do texto (totais costumam ser o maior). */
    private static function amount(string $text): ?float
    {
        preg_match_all('/R\$\s?([\d.,()\-+]+)/i', $text, $m);
        $best = null;
        foreach ($m[1] as $raw) {
            $v = self::parseAmount($raw);
            if ($v !== null && ($best === null || abs($v) > abs($best))) {
                $best = $v;
            }
        }

        return $best === null ? null : abs(round($best, 2));
    }

    private static function parseAmount(string $raw): ?float
    {
        $v = trim($raw);
        if ($v === '') {
            return null;
        }
        $negative = false;
        if (str_starts_with($v, '(') && str_ends_with($v, ')')) {
            $negative = true;
            $v = substr($v, 1, -1);
        }
        $v = trim($v, ' +-');
        $v = str_replace(['R$', '$', ' '], '', $v);
        if ($v === '' || ! preg_match('/^[\d.,]+$/', $v)) {
            return null;
        }
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        if (! is_numeric($v)) {
            return null;
        }

        $amount = round((float) $v, 2);

        return $negative ? -$amount : $amount;
    }

    /** Primeira data válida (dd/mm/aaaa) próxima de palavras de data. */
    private static function date(string $text): ?string
    {
        preg_match_all('#(\d{1,2})/(\d{1,2})/(\d{2,4})#', $text, $m, PREG_SET_ORDER);
        foreach ($m as $d) {
            $year = (int) $d[3] < 100 ? (int) $d[3] + 2000 : (int) $d[3];
            if (checkdate((int) $d[2], (int) $d[1], $year)) {
                return sprintf('%04d-%02d-%02d', $year, $d[2], $d[1]);
            }
        }

        return null;
    }

    private static function bank(string $norm): ?string
    {
        foreach (self::BANKS as $name => $words) {
            foreach ($words as $w) {
                if (str_contains($norm, trim($w))) {
                    return $name;
                }
            }
        }

        return null;
    }

    private static function description(array $lines, string $channel): string
    {
        // Tenta linhas de favorecido/beneficiário/parceiro.
        foreach ($lines as $i => $line) {
            $low = mb_strtolower($line);
            if (preg_match('/(favorecido|beneficiario|para|destinatario|estabelecimento|loja)\s*:?\s*(.+)/ui', $line, $m) && trim($m[2]) !== '') {
                unset($lines[$i]);
                $candidate = trim($m[2]);
                if (mb_strlen($candidate) > 2) {
                    return mb_substr($candidate, 0, 120);
                }
            }
            unset($low);
        }
        // Senão, a primeira linha relevante que não seja título/valor.
        foreach ($lines as $line) {
            if (mb_strlen($line) < 3 || preg_match('/R\$|comprovante|recibo|\d{2}\/\d{2}\/\d{2,4}/ui', $line)) {
                continue;
            }

            return mb_substr($line, 0, 120);
        }

        return $channel === 'pix' ? 'Pix' : 'Comprovante';
    }
}
