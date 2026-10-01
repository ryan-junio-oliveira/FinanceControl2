<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Support\CategoryCatalog;
use Illuminate\Console\Command;

class PruneCategories extends Command
{
    protected $signature = 'categories:prune {--family= : ID da família (padrão: todas as famílias)}';

    protected $description = 'Consolida categorias detalhadas nos temas (move lançamentos e apaga excedentes)';

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

        foreach ($families as $family) {
            $r = CategoryCatalog::pruneForFamily($family);
            $this->line("Família #{$family->id} ({$family->name}): {$r['moved']} lançamento(s) movidos, {$r['removed']} categoria(s) removida(s).");
        }

        $this->info('Concluído.');

        return self::SUCCESS;
    }
}
