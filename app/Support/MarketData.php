<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Indicadores de mercado para a tela de investimentos.
 *
 * Fontes públicas, sem chave: Banco Central (SGS), Yahoo Finance,
 * AwesomeAPI e Binance. Resultado em cache por 30 min; qualquer
 * falha retorna o item como null (a view exibe "indisponível").
 */
final class MarketData
{
    private const CACHE_KEY = 'market:snapshot';

    private const TTL = 1800;

    /**
     * @return array{selic: ?array, cdi: ?array, ibovespa: ?array, dolar: ?array, btc_usd: ?array, btc_brl: ?array, fetched_at: ?string}
     */
    public static function snapshot(): array
    {
        if (app()->environment('testing')) {
            return self::empty();
        }

        return Cache::remember(self::CACHE_KEY, self::TTL, fn () => [
            'selic' => self::selicFromRows(self::bcb(432, 2)),
            'cdi' => self::cdiFromRows(self::bcb(12, 1)),
            'ibovespa' => self::ibovespaFromMeta(self::yahooMeta()),
            'dolar' => self::dolarFromRow(self::awesomeUsdBrl()),
            'btc_usd' => self::bitcoinFromRow(self::binance('BTCUSDT')),
            'btc_brl' => self::bitcoinFromRow(self::binance('BTCBRL')),
            'fetched_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    public static function empty(): array
    {
        return [
            'selic' => null, 'cdi' => null, 'ibovespa' => null,
            'dolar' => null, 'btc_usd' => null, 'btc_brl' => null,
            'fetched_at' => null,
        ];
    }

    /** Selic meta (% a.a.) + variação em p.p. vs dia útil anterior. */
    public static function selicFromRows(array $rows): ?array
    {
        if (count($rows) < 1) {
            return null;
        }
        $last = (float) str_replace(',', '.', end($rows)['valor']);
        $prev = count($rows) > 1 ? (float) str_replace(',', '.', $rows[count($rows) - 2]['valor']) : $last;

        return ['value' => $last, 'change' => round($last - $prev, 2), 'date' => end($rows)['data']];
    }

    /** CDI diário annualizado por 252 dias úteis (% a.a. estimado). */
    public static function cdiFromRows(array $rows): ?array
    {
        if (empty($rows)) {
            return null;
        }
        $daily = (float) str_replace(',', '.', $rows[0]['valor']);

        return ['value' => round(((1 + $daily / 100) ** 252 - 1) * 100, 2), 'change' => null, 'date' => $rows[0]['data']];
    }

    /** Ibovespa em pontos + variação do dia (%). */
    public static function ibovespaFromMeta(mixed $meta): ?array
    {
        if (! is_array($meta) || ! isset($meta['regularMarketPrice'])) {
            return null;
        }

        return [
            'value' => (float) $meta['regularMarketPrice'],
            'change' => isset($meta['regularMarketChangePercent']) ? round((float) $meta['regularMarketChangePercent'], 2) : null,
            'date' => isset($meta['regularMarketTime']) ? date('d/m/Y', (int) $meta['regularMarketTime']) : null,
        ];
    }

    /** Dólar comercial (venda) + variação do dia (%). */
    public static function dolarFromRow(mixed $row): ?array
    {
        if (! is_array($row) || ! isset($row['bid'])) {
            return null;
        }

        return [
            'value' => (float) $row['bid'],
            'change' => isset($row['pctChange']) ? round((float) $row['pctChange'], 2) : null,
            'date' => $row['create_date'] ?? null,
        ];
    }

    /** Bitcoin (Binance, 24h) + variação (%). */
    public static function bitcoinFromRow(mixed $row): ?array
    {
        if (! is_array($row) || ! isset($row['lastPrice'])) {
            return null;
        }

        return [
            'value' => (float) $row['lastPrice'],
            'change' => isset($row['priceChangePercent']) ? round((float) $row['priceChangePercent'], 2) : null,
            'date' => null,
        ];
    }

    private static function yahooMeta(): mixed
    {
        try {
            return Http::timeout(4)->get('https://query1.finance.yahoo.com/v8/finance/chart/%5EBVSP', [
                'interval' => '1d', 'range' => '5d',
            ])->json('chart.result.0.meta');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function awesomeUsdBrl(): mixed
    {
        try {
            return Http::timeout(4)->get('https://economia.awesomeapi.com.br/json/last/USD-BRL')->json('USDBRL');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function binance(string $symbol): mixed
    {
        try {
            return Http::timeout(4)->get("https://api.binance.com/api/v3/ticker/24hr?symbol={$symbol}")->json();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Últimos N pontos de uma série do BCB. */
    private static function bcb(int $serie, int $last): array
    {
        try {
            $rows = Http::timeout(4)
                ->get("https://api.bcb.gov.br/dados/serie/bcdata.sgs.{$serie}/dados/ultimos/{$last}", ['formato' => 'json'])
                ->json();
        } catch (\Throwable) {
            return [];
        }

        return is_array($rows) ? $rows : [];
    }
}
