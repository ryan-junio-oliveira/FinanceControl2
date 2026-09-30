<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Indicadores + ações renovados a cada 15 min (TTLs de 30/15 min).
Schedule::command('market:warm')->everyFifteenMinutes();

// Avisos de vencimento todo dia às 08:00; resumo semanal às segundas 08:00.
Schedule::command('notify:vencimentos')->dailyAt('08:00');
Schedule::command('notify:resumo')->weeklyOn(1, '08:00');
