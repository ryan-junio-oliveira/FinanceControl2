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
        $lines = [
            BotPresenter::header('Investimentos'),
            'Patrimônio: <b>'.BotPresenter::money((float) $dados['patrimonio']).'</b>',
            '',
            '💰 Aportes no mês: <b>'.BotPresenter::money((float) $dados['aportesMes']).'</b>',
            '📈 Rendimentos no mês: <b>'.BotPresenter::money((float) $dados['rendMes']).'</b>',
            '',
            '💡 Aportes e rendimentos pelo sistema.',
        ];
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }
}
