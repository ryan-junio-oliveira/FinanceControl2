<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Indicadores de mercado renovados a cada 30 min (mesmo TTL do cache).
Schedule::command('market:warm')->everyThirtyMinutes();
