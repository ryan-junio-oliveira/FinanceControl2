<?php

namespace App\Bot\Handlers;

use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\BotIdentity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LinkHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $this->tryLink($driver, $msg, trim($msg->text));
    }

    public function start(BotDriver $driver, IncomingMessage $msg, ?string $code): void
    {
        if ($code !== null && $code !== '' && $this->tryLink($driver, $msg, $code)) {
            return;
        }

        $identity = BotIdentity::where('channel', $msg->channel)->where('external_id', $msg->chatId)->first();
        if ($identity) {
            $this->showMenu($driver, $msg, $identity->user);

            return;
        }

        $this->askCode($driver, $msg);
        ConversationState::put($msg->channel, $msg->chatId, static::class, 'code');
    }

    public function askCode(BotDriver $driver, IncomingMessage $msg): void
    {
        $driver->sendText(
            $msg->chatId,
            "👋 Olá! Para usar o FinFamília, vincule sua conta.\n\nDigite o <b>código de 6 dígitos</b> que aparece no seu perfil no sistema.",
            $this->cancelKeyboard()
        );
    }

    private function tryLink(BotDriver $driver, IncomingMessage $msg, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        $user = $code !== '' ? User::where('bot_code', $code)->first() : null;

        if (! $user) {
            $driver->sendText($msg->chatId, '❌ Código inválido. Confira no seu perfil e tente de novo.', $this->cancelKeyboard());
            ConversationState::put($msg->channel, $msg->chatId, static::class, 'code');

            return false;
        }

        BotIdentity::updateOrCreate(
            ['channel' => $msg->channel, 'external_id' => $msg->chatId],
            ['user_id' => $user->id]
        );
        Auth::setUser($user);
        $driver->sendText($msg->chatId, '✅ Conta vinculada! Bem-vindo(a), '.explode(' ', $user->name)[0].'.');
        $this->showMenu($driver, $msg, $user);

        return true;
    }
}
