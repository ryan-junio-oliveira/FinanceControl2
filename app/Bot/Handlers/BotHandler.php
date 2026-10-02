<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;

abstract class BotHandler
{
    abstract public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void;

    protected function menuKeyboard(): BotKeyboard
    {
        return BotKeyboard::menu([
            '1️⃣ Dados financeiros' => 'menu:dados',
            '2️⃣ Despesas' => 'menu:despesas',
            '3️⃣ Receitas' => 'menu:receitas',
            '4️⃣ Cartões' => 'menu:cartoes',
            '5️⃣ Contas' => 'menu:contas',
            '6️⃣ Investimentos' => 'menu:investimentos',
            '7️⃣ Mercado' => 'menu:mercado',
        ]);
    }

    protected function showMenu(BotDriver $driver, IncomingMessage $msg, ?User $user, bool $unknown = false): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $hello = $user ? ', '.explode(' ', $user->name)[0] : '';
        $prefix = $unknown ? "🤔 Não entendi. Escolha uma opção:\n\n" : '';
        $text = $prefix.'🏠 <b>FinFamília</b>'.$hello."\n".BotPresenter::divider()."\nEscolha uma opção:";
        $driver->sendText($msg->chatId, $text, $this->menuKeyboard());
    }

    protected function ask(BotDriver $driver, IncomingMessage $msg, string $step, array $data, string $text, ?BotKeyboard $keyboard = null): void
    {
        ConversationState::put($msg->channel, $msg->chatId, static::class, $step, $data);
        $driver->sendText($msg->chatId, $text, $keyboard);
    }

    protected function done(BotDriver $driver, IncomingMessage $msg, ?User $user, string $text): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $driver->sendText($msg->chatId, $text, $this->menuKeyboard());
    }

    protected function cancelKeyboard(): BotKeyboard
    {
        return BotKeyboard::menu(['❌ Cancelar' => 'cancelar']);
    }

    protected function confirmKeyboard(): BotKeyboard
    {
        return BotKeyboard::menu(['✅ Confirmar' => 'sim', '❌ Cancelar' => 'nao']);
    }

    protected static function isYes(string $text): bool
    {
        return in_array(mb_strtolower(trim($text)), ['sim', 's', 'yes', 'y', '1', 'confirmar', 'confirmo', 'ok'], true);
    }

    protected static function isNo(string $text): bool
    {
        return in_array(mb_strtolower(trim($text)), ['nao', 'não', 'n', 'no', '2'], true);
    }

    protected static function num(string $text): ?int
    {
        $text = trim($text);
        if (! preg_match('/^\d{1,3}$/', $text)) {
            return null;
        }

        return (int) $text;
    }

    protected function numberedList(array $lines): string
    {
        $out = [];
        foreach ($lines as $i => $line) {
            $n = $i + 1;
            $num = $n <= 9 ? $n.'️⃣' : '<b>'.$n.'</b>';
            $out[] = $num.' '.$line;
        }

        return implode("\n", $out);
    }

    protected function presenter(): BotPresenter
    {
        return new BotPresenter;
    }
}
