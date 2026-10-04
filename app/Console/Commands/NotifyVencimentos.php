<?php

namespace App\Console\Commands;

use App\Bot\BotNotifier;
use App\Jobs\SendGroupVencimentos;
use App\Models\Group;
use Illuminate\Console\Command;

class NotifyVencimentos extends Command
{
    protected $signature = 'notify:vencimentos';

    protected $description = 'Notifica faturas de cartão e contas próximas do vencimento (hoje, amanhã ou em até 3 dias)';

    public function handle(): int
    {
        // Fan-out: 1 job por grupo (chunked, sem carregar tudo em memória).
        // O scheduler retorna rápido; o trabalho pesado roda nos workers.
        $total = 0;
        Group::query()->select('id')->chunkById(200, function ($groups) use (&$total) {
            foreach ($groups as $group) {
                SendGroupVencimentos::dispatch($group->id)->onQueue('notifications');
                $total++;
            }
        });

        $this->info("Grupos enfileiradas para aviso de vencimento: {$total}.");

        $bot = BotNotifier::vencimentos();
        $this->info("Alertas no Telegram: {$bot}.");

        return self::SUCCESS;
    }
}
