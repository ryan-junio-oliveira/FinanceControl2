<?php

namespace App\Console\Commands;

use App\Support\BankCatalog;
use Illuminate\Console\Command;

class SeedBanks extends Command
{
    protected $signature = 'banks:seed';

    protected $description = 'Semeia o catálogo global de bancos (idempotente, sem duplicar)';

    public function handle(): int
    {
        $created = BankCatalog::seed();
        $this->info("Concluído. Bancos criados: {$created}.");

        return self::SUCCESS;
    }
}
