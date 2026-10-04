<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Indicadores + ações renovados a cada 15 min (TTLs de 30/15 min).
Schedule::command('market:warm')->everyFifteenMinutes();

// Avisos de vencimento (faturas e contas) todo dia às 08:00.
Schedule::command('notify:vencimentos')->dailyAt('08:00');

// Backup lógico do MySQL todo dia às 03:00 (sem sobrepor execuções).
// Em dev/sqlite o comando só avisa e sai (sem falhar o scheduler).
Schedule::command('db:backup')->dailyAt('03:00')->withoutOverlapping(60);
