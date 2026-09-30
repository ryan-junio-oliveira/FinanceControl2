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
    private const CACHE_KEY = 'market:snapshot:v2';

    private const TTL = 1800;

    private const STOCKS_TTL = 900;

    /**
     * @return array{selic: ?array, cdi: ?array, ibovespa: ?array, dolar: ?array, btc_usd: ?array, btc_brl: ?array, fetched_at: ?string}
     */
    public static function snapshot(): array
    {
        if (app()->environment('testing')) {
            return self::empty();
        }

        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $selicRows = self::bcb(432, 5);
            $selic = self::selicFromRows($selicRows);

            return [
                'selic' => $selic,
                'cdi' => self::cdiFromSelic($selic),
                'ibovespa' => self::ibovespaFromMeta(self::yahooMeta()),
                'dolar' => self::dolarFromRow(self::awesomeUsdBrl()),
                'btc_usd' => self::bitcoinFromRow(self::binance('BTCUSDT')),
                'btc_brl' => self::bitcoinFromRow(self::binance('BTCBRL')),
                'fetched_at' => now()->format('d/m/Y H:i'),
            ];
        });
    }

    public static function empty(): array
    {
        return [
            'selic' => null, 'cdi' => null, 'ibovespa' => null,
            'dolar' => null, 'btc_usd' => null, 'btc_brl' => null,
            'fetched_at' => null,
        ];
    }

    /**
     * Cotações de ações/ETFs: Brapi (se BRAPI_TOKEN) ou Yahoo (pool concorrente).
     *
     * @return array{items: array<int, array{code: string, label: string, price: float, change: ?float}>, fetched_at: ?string}
     */
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

    /** CDI ≈ Selic − 0,10 p.p. (convenção de mercado, sem fonte própria). */
    public static function cdiFromSelic(?array $selic): ?array
    {
        if ($selic === null) {
            return null;
        }

        return ['value' => round($selic['value'] - 0.10, 2), 'change' => null, 'date' => $selic['date'] ?? null];
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

    /** Linha de ação/ETF a partir do meta do Yahoo (v8 chart). */
    public static function stockFromYahooMeta(string $code, string $label, mixed $meta): ?array
    {
        if (! is_array($meta) || ! isset($meta['regularMarketPrice'])) {
            return null;
        }

        return [
            'code' => $code,
            'label' => $label !== '' ? $label : (string) ($meta['shortName'] ?? $code),
            'price' => (float) $meta['regularMarketPrice'],
            'change' => isset($meta['regularMarketChangePercent']) ? round((float) $meta['regularMarketChangePercent'], 2) : null,
        ];
    }

    /** Linha de ação/ETF a partir de um resultado da Brapi. */
    public static function stockFromBrapiRow(string $label, mixed $row): ?array
    {
        if (! is_array($row) || ! isset($row['regularMarketPrice'], $row['symbol'])) {
            return null;
        }

        return [
            'code' => (string) $row['symbol'],
            'label' => $label,
            'price' => (float) $row['regularMarketPrice'],
            'change' => isset($row['regularMarketChangePercent']) ? round((float) $row['regularMarketChangePercent'], 2) : null,
        ];
    }

    /** Carteira aproximada do Ibovespa (inválidos são filtrados pelo Yahoo). */
    public const IBOV = [
        'ABEV3', 'ALPA4', 'ALLOS3', 'AMOB3', 'ASAI3', 'AZZA3', 'B3SA3', 'BBAS3',
        'BBDC3', 'BBDC4', 'BBSE3', 'BEEF3', 'BPAC11', 'BRAP4', 'BRFS3', 'BRKM5',
        'CCRO3', 'CMIG4', 'CMIN3', 'COGN3', 'CPFE3', 'CPLE6', 'CSAN3', 'CSNA3',
        'CVCB3', 'CXSE3', 'CYRE3', 'DXCO3', 'ELET3', 'ELET6', 'EMBR3', 'ENEV3',
        'EQTL3', 'EZTC3', 'FLRY3', 'GGBR4', 'GOAU4', 'HAPV3', 'HYPE3', 'IGTI11',
        'IRBR3', 'ITSA4', 'ITUB4', 'JBSS3', 'KLBN11', 'LREN3', 'LWSA3', 'MGLU3',
        'MRFG3', 'MRVE3', 'MULT3', 'NTCO3', 'PETR3', 'PETR4', 'PETZ3', 'PRIO3',
        'PSSA3', 'RADL3', 'RAIZ4', 'RAIL3', 'RENT3', 'SANB11', 'SBSP3', 'SMTO3',
        'STBP3', 'SUZB3', 'TAEE11', 'TIMS3', 'TOTS3', 'UGPA3', 'USIM5', 'VALE3',
        'VAMO3', 'VBBR3', 'VIVA3', 'VIVT3', 'WEGE3', 'YDUQ3', 'ALOS3', 'AURE3',
        'GMAT3', 'INTB3', 'JSLG3', 'ODPV3', 'PGMN3', 'SBFG3', 'SIMH3', 'TUPY3',
        'VITT3', 'TTEN3', 'AGRO3', 'NEOE3', 'ZAMP3', 'GGPS3', 'TFCO4', 'WIZC3',
    ];

    /** FIIs de reserva caso a Brapi falhe. */
    public const FII_FALLBACK = [
        'HGLG11', 'KNRI11', 'KNCR11', 'KNIP11', 'XPML11', 'VISC11', 'HGRE11',
        'LVBI11', 'BRCR11', 'HGRU11', 'MALL11', 'ALZR11', 'BTLG11', 'TRXF11',
        'VILG11', 'GGRC11', 'JSRE11', 'KNSC11', 'KNFA11', 'MXRF11', 'HFOF11',
        'RBRF11', 'RECR11', 'CPTS11', 'DEVA11', 'IRDM11', 'XPLG11', 'PVBI11',
    ];

    /**
     * Payload completo da página Mercado: indicadores + ações + FIIs.
     *
     * @return array{indicators: array, acoes: array, fiis: array, fetched_at: ?string}
     */
    public static function mercado(): array
    {
        if (app()->environment('testing')) {
            return ['indicators' => self::empty(), 'acoes' => [], 'fiis' => [], 'fetched_at' => null];
        }

        return Cache::remember('market:mercado:v1', self::STOCKS_TTL, function () {
            return [
                'indicators' => self::snapshot(),
                'acoes' => self::ibovStocks(),
                'fiis' => self::fiiList(),
                'fetched_at' => now()->format('d/m/Y H:i'),
            ];
        });
    }

    /** Ações do Ibovespa via Yahoo em pool concorrente. */
    public static function ibovStocks(): array
    {
        return self::yahooBatch(self::IBOV);
    }

    /** FIIs via Brapi paginado (100); fallback Yahoo curado. */
    public static function fiiList(): array
    {
        $items = [];
        $page = 1;
        try {
            while (count($items) < 100 && $page <= 4) {
                $json = Http::timeout(8)->get('https://brapi.dev/api/quote/list', [
                    'type' => 'fund', 'limit' => 100, 'page' => $page,
                ])->json();
                $rows = is_array($json) ? ($json['stocks'] ?? []) : [];
                if ($rows === []) {
                    break;
                }
                foreach ($rows as $row) {
                    if (($row['subType'] ?? '') !== 'fii' || ! isset($row['close'])) {
                        continue;
                    }
                    $items[] = [
                        'code' => (string) $row['stock'],
                        'label' => (string) ($row['name'] ?? $row['stock']),
                        'price' => (float) $row['close'],
                        'change' => isset($row['change']) ? round((float) $row['change'], 2) : null,
                        'segment' => $row['subsector'] ?? null,
                    ];
                    if (count($items) >= 100) {
                        break;
                    }
                }
                if (! ($json['hasNextPage'] ?? false)) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable) {
            // cai no fallback abaixo
        }

        if ($items === []) {
            foreach (self::yahooBatch(self::FII_FALLBACK) as $fii) {
                $fii['segment'] = null;
                $items[] = $fii;
            }
        }

        return $items;
    }

    /** Cotações Yahoo em paralelo para uma lista de códigos (sem .SA). */
    public static function yahooBatch(array $codes): array
    {
        try {
            $responses = Http::pool(function ($pool) use ($codes) {
                foreach ($codes as $code) {
                    $pool->as($code)->timeout(5)
                        ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$code}.SA", [
                            'interval' => '1d', 'range' => '2d',
                        ]);
                }
            });
        } catch (\Throwable) {
            return [];
        }

        $items = [];
        foreach ($codes as $code) {
            try {
                $meta = $responses[$code]->json('chart.result.0.meta') ?? null;
            } catch (\Throwable) {
                continue;
            }
            $item = self::stockFromYahooMeta($code, '', $meta);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
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
