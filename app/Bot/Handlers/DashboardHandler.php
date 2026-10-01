<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Support\Dashboard;
use App\Support\Fin;

class DashboardHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->show($driver, $msg, $user);
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $mes = Fin::month();
        $dados = Dashboard::data($mes);
        $driver->sendText($msg->chatId, BotPresenter::dashboardText($dados, $mes), $this->menuKeyboard());
    }
}
