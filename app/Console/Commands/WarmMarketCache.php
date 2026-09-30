<?php

namespace App\Console\Commands;

use App\Support\MarketData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmMarketCache extends Command
{
    protected $signature = 'market:warm';

    protected $description = 'Atualiza o cache de indicadores de mercado (Selic, Ibovespa, dólar, Bitcoin)';

    public function handle(): int
    {
        Cache::forget('market:snapshot');
        $snap = MarketData::snapshot();
        $ok = collect([$snap['selic'], $snap['ibovespa'], $snap['dolar'], $snap['btc_usd']])->filter()->count();

        $this->info("Cache de mercado atualizado: {$ok}/6 indicadores disponíveis.");

        return self::SUCCESS;
    }
}
