<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Services\AccountService;

class AccountHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->show($driver, $msg, $user);
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $contas = app(AccountService::class)->list($user->family);
        $total = 0.0;
        $lines = ['🏦 <b>Contas</b>', ''];
        foreach ($contas as $c) {
            $total += (float) $c->balance;
            $lines[] = '• '.$c->name.' ('.($c->bank->name ?? '—').') — <b>'.BotPresenter::money((float) $c->balance).'</b>';
        }
        $lines[] = '';
        $lines[] = 'Total: <b>'.BotPresenter::money($total).'</b>';
        if ($contas->isEmpty()) {
            $lines[] = 'Nenhuma conta cadastrada.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }
}
