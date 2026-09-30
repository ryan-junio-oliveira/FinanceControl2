<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Support\CategoryCatalog;
use Illuminate\Console\Command;

class SeedCategories extends Command
{
    protected $signature = 'categories:seed {--family= : ID da família (padrão: todas as famílias)}';

    protected $description = 'Semeia o catálogo padrão de categorias (idempotente, sem duplicar)';

    public function handle(): int
    {
        $query = Family::query();
        if ($this->option('family')) {
            $query->where('id', $this->option('family'));
        }

        $families = $query->get();
        if ($families->isEmpty()) {
            $this->warn('Nenhuma família encontrada.');

            return self::FAILURE;
        }

        $total = 0;
        foreach ($families as $family) {
            $created = CategoryCatalog::seedForFamily($family);
            $total += $created;
            $this->line("Família #{$family->id} ({$family->name}): {$created} categorias criadas.");
        }

        $this->info("Concluído. Total de categorias criadas: {$total}.");

        return self::SUCCESS;
    }
}
