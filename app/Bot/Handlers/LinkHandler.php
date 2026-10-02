<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\BotIdentity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            "👋 <b>Bem-vindo ao Prumo!</b>\n"
            .BotPresenter::divider()."\n"
            ."Para usar o bot, vincule sua conta.\n\nDigite o <b>código de 6 dígitos</b> que aparece em <b>Perfil → Bot no Celular</b> no sistema.",
            $this->cancelKeyboard()
        );
    }

    private function tryLink(BotDriver $driver, IncomingMessage $msg, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);

        // Anti-força-bruta: 10 tentativas erradas em 10 min bloqueiam temporariamente.
        $key = 'bot:link:'.$msg->channel.':'.$msg->chatId;
        if ((int) Cache::get($key, 0) >= 10) {
            $driver->sendText($msg->chatId, '⏳ Muitas tentativas. Aguarde alguns minutos e tente de novo.', $this->cancelKeyboard());

            return false;
        }

        $user = $code !== '' ? User::where('bot_code', $code)->first() : null;

        // Código de uso único com validade: sem expiração futura, não vincula.
        if (! $user || ! $user->bot_code_expires_at || $user->bot_code_expires_at->isPast()) {
            Cache::put($key, (int) Cache::get($key, 0) + 1, now()->addMinutes(10));
            $driver->sendText($msg->chatId, '❌ Código inválido ou expirado. Gere um novo no seu perfil e tente de novo.', $this->cancelKeyboard());
            ConversationState::put($msg->channel, $msg->chatId, static::class, 'code');

            return false;
        }

        Cache::forget($key);

        BotIdentity::updateOrCreate(
            ['channel' => $msg->channel, 'external_id' => $msg->chatId],
            ['user_id' => $user->id]
        );
        // Uso único: o código morre após vincular.
        $user->update(['bot_code' => null, 'bot_code_expires_at' => null]);
        Auth::setUser($user);
        $driver->sendText($msg->chatId, '✅ <b>Conta vinculada!</b> Bem-vindo(a), '.e(explode(' ', $user->name)[0]).'.');
        $this->showMenu($driver, $msg, $user);

        return true;
    }
}
