<?php

namespace App\Console\Commands;

use App\Support\MarketData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmMarketCache extends Command
{
    protected $signature = 'market:warm';

    protected $description = 'Atualiza o cache da página Mercado (indicadores, ações, FIIs)';

    public function handle(): int
    {
        Cache::forget('market:snapshot');
        Cache::forget('market:snapshot:v2');
        Cache::forget('market:stocks:v1');
        Cache::forget('market:mercado:v1');
        $data = MarketData::mercado();
        $ind = $data['indicators'];
        $ok = collect([$ind['selic'], $ind['ibovespa'], $ind['dolar'], $ind['btc_usd']])->filter()->count();

        $this->info("Cache de mercado atualizado: {$ok}/6 indicadores + ".count($data['acoes']).' ações + '.count($data['fiis']).' FIIs.');

        return self::SUCCESS;
    }
}
