<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Support\CategoryCatalog;
use Illuminate\Database\Seeder;

class CategoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $total = 0;
        Family::query()->each(function (Family $family) use (&$total) {
            $total += CategoryCatalog::seedForFamily($family);
        });

        $this->command?->info("Categorias do catálogo criadas: {$total}.");
    }
}
