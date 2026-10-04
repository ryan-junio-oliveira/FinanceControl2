<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Support\CategoryCatalog;
use Illuminate\Console\Command;

class SeedCategories extends Command
{
    protected $signature = 'categories:seed {--group= : ID do grupo (padrão: todos os grupos)}';

    protected $description = 'Semeia o catálogo padrão de categorias (idempotente, sem duplicar)';

    public function handle(): int
    {
        $query = Group::query();
        if ($this->option('group')) {
            $query->where('id', $this->option('group'));
        }

        $groups = $query->get();
        if ($groups->isEmpty()) {
            $this->warn('Nenhum grupo encontrado.');

            return self::FAILURE;
        }

        $total = 0;
        foreach ($groups as $group) {
            $created = CategoryCatalog::seedForGroup($group);
            $total += $created;
            $this->line("Grupo #{$group->id} ({$group->name}): {$created} categorias criadas.");
        }

        $this->info("Concluído. Total de categorias criadas: {$total}.");

        return self::SUCCESS;
    }
}
