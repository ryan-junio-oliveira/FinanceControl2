<?php

namespace Database\Seeders;

use App\Support\BankCatalog;
use Illuminate\Database\Seeder;

class BankCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $total = BankCatalog::seed();

        $this->command?->info("Bancos do catálogo criados: {$total}.");
    }
}
