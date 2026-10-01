<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Services\InvestmentService;

class InvestmentHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->show($driver, $msg, $user);
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $dados = app(InvestmentService::class)->dashboard($user->family);
        $lines = [BotPresenter::header('Investimentos'), 'Patrimônio: <b>'.BotPresenter::money((float) $dados['patrimonio']).'</b>', ''];
        foreach ($dados['metas'] as $meta) {
            $pct = (float) ($meta->progress ?? 0);
            $lines[] = '🎯 <b>'.$meta->name.'</b>';
            $lines[] = '   '.BotPresenter::bar($pct);
            $lines[] = '   '.BotPresenter::money((float) $meta->total).' de '.BotPresenter::money((float) $meta->target_amount);
        }
        if ($dados['metas']->isEmpty()) {
            $lines[] = 'Nenhuma meta configurada.';
        }
        $lines[] = '';
        $lines[] = '💡 Aportes e rendimentos pelo sistema.';
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }
}
