<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Support\MarketData;

class MarketHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->show($driver, $msg, $user);
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $snap = MarketData::snapshot();
        $movers = MarketData::movers(5);

        $lines = [BotPresenter::header('Mercado'), ''];
        $lines[] = '📈 <b>Selic:</b> '.self::taxa($snap['selic']).' a.a.';
        $lines[] = '🏦 <b>CDI:</b> '.self::taxa($snap['cdi']).' a.a.';
        $lines[] = '🇧🇷 <b>Ibovespa:</b> '.self::pontos($snap['ibovespa']);
        $lines[] = '💵 <b>Dólar:</b> '.self::reais($snap['dolar']);
        $lines[] = '₿ <b>Bitcoin:</b> '.self::reais($snap['btc_brl'] ?? $snap['btc_usd']);

        $lines[] = '';
        $lines[] = BotPresenter::divider();
        $lines[] = '🚀 <b>Maiores altas</b>';
        $lines[] = self::stocks($movers['up'], 'Nenhuma alta disponível.');
        $lines[] = '';
        $lines[] = '📉 <b>Maiores quedas</b>';
        $lines[] = self::stocks($movers['down'], 'Nenhuma queda disponível.');

        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }

    private static function taxa(?array $item): string
    {
        if (! $item || ! isset($item['value'])) {
            return 'indisponível';
        }
        $change = isset($item['change']) ? ' ('.BotPresenter::change($item['change'], ' p.p.').')' : '';

        return '<b>'.number_format((float) $item['value'], 2, ',', '.').'%</b>'.$change;
    }

    private static function pontos(?array $item): string
    {
        if (! $item || ! isset($item['value'])) {
            return 'indisponível';
        }
        $change = isset($item['change']) ? ' ('.BotPresenter::change($item['change']).')' : '';

        return '<b>'.number_format((float) $item['value'], 0, ',', '.').' pts</b>'.$change;
    }

    private static function reais(?array $item): string
    {
        if (! $item || ! isset($item['value'])) {
            return 'indisponível';
        }
        $change = isset($item['change']) ? ' ('.BotPresenter::change($item['change']).')' : '';

        return '<b>'.BotPresenter::money((float) $item['value']).'</b>'.$change;
    }

    /** @param array<int, array{code: string, change: ?float, kind: string}> $stocks */
    private static function stocks(array $stocks, string $empty): string
    {
        if ($stocks === []) {
            return $empty;
        }
        $lines = [];
        foreach ($stocks as $s) {
            $tipo = $s['kind'] === 'fii' ? '🏢' : '📈';
            $lines[] = '• '.$tipo.' <b>'.e($s['code']).'</b> '.BotPresenter::change($s['change'] ?? null).' · '.($s['kind'] === 'fii' ? 'FII' : 'Ação');
        }

        return implode("\n", $lines);
    }
}
