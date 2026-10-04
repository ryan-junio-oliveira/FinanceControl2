<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Support\CategoryCatalog;
use Illuminate\Console\Command;

class PruneCategories extends Command
{
    protected $signature = 'categories:prune {--group= : ID do grupo (padrão: todos os grupos)}';

    protected $description = 'Consolida categorias detalhadas nos temas (move lançamentos e apaga excedentes)';

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

        foreach ($groups as $group) {
            $r = CategoryCatalog::pruneForGroup($group);
            $this->line("Grupo #{$group->id} ({$group->name}): {$r['moved']} lançamento(s) movidos, {$r['removed']} categoria(s) removida(s).");
        }

        $this->info('Concluído.');

        return self::SUCCESS;
    }
}
