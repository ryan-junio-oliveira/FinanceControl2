<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Support\CategoryCatalog;
use Illuminate\Database\Seeder;

class CategoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $total = 0;
        Group::query()->each(function (Group $group) use (&$total) {
            $total += CategoryCatalog::seedForGroup($group);
        });

        $this->command?->info("Categorias do catálogo criadas: {$total}.");
    }
}
