<?php

namespace App\Bot\Handlers;

use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;

class MenuHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->show($driver, $msg, $user);
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user = null, bool $unknown = false): void
    {
        $this->showMenu($driver, $msg, $user, $unknown);
    }

    public function option(BotDriver $driver, IncomingMessage $msg, ?User $user, string $option): void
    {
        match ($option) {
            'dados' => app(DashboardHandler::class)->show($driver, $msg, $user),
            'despesas' => app(ExpenseHandler::class)->menu($driver, $msg, $user),
            'receitas' => app(IncomeHandler::class)->menu($driver, $msg, $user),
            'cartoes' => app(CardHandler::class)->menu($driver, $msg, $user),
            'contas' => app(AccountHandler::class)->show($driver, $msg, $user),
            'investimentos' => app(InvestmentHandler::class)->show($driver, $msg, $user),
            default => $this->show($driver, $msg, $user, true),
        };
    }

    public static function submenuKeyboard(array $options): BotKeyboard
    {
        $rows = [];
        foreach ($options as $label => $data) {
            $rows[] = [[$label, $data]];
        }
        $rows[] = [['🔙 Menu' => 'menu']];

        return BotKeyboard::inline($rows);
    }
}
