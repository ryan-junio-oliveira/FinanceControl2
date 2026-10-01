<?php

namespace App\Bot\Drivers;

use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Driver do Telegram (Bot API — grátis e ilimitada). */
final class TelegramDriver implements BotDriver
{
    public function name(): string
    {
        return 'telegram';
    }

    public function sendText(string $chatId, string $text, ?BotKeyboard $keyboard = null): void
    {
        $token = (string) config('bot.telegram.token');
        if ($token === '') {
            Log::warning('[bot] Telegram sem token; mensagem descartada.');

            return;
        }

        $payload = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
        if ($keyboard) {
            $payload['reply_markup'] = ['inline_keyboard' => array_map(
                fn ($row) => array_map(
                    fn ($btn) => ['text' => $btn[0], 'callback_data' => mb_substr($btn[1], 0, 64)],
                    $row
                ),
                $keyboard->rows
            )];
        }

        Http::baseUrl(config('bot.telegram.api')."/bot{$token}")
            ->timeout(10)
            ->post('/sendMessage', $payload)
            ->throw(fn ($res, $e) => Log::error('[bot] Falha ao enviar Telegram: '.$e->getMessage()));
    }

    public function parseWebhook(Request $request): ?IncomingMessage
    {
        $secret = (string) config('bot.telegram.secret');
        if ($secret !== '' && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return null;
        }

        $update = $request->json()->all();

        // Botão inline (callback_query).
        if (isset($update['callback_query'])) {
            $cb = $update['callback_query'];
            $chatId = (string) ($cb['message']['chat']['id'] ?? $cb['from']['id'] ?? '');

            return $chatId === '' ? null : new IncomingMessage(
                channel: 'telegram',
                chatId: $chatId,
                text: (string) ($cb['data'] ?? ''),
                fromName: $cb['from']['first_name'] ?? null,
            );
        }

        // Mensagem de texto.
        $msg = $update['message'] ?? null;
        if (! is_array($msg) || ! isset($msg['chat']['id'])) {
            return null;
        }
        $text = trim((string) ($msg['text'] ?? ''));
        if ($text === '') {
            return null;
        }

        return new IncomingMessage(
            channel: 'telegram',
            chatId: (string) $msg['chat']['id'],
            text: $text,
            fromName: $msg['from']['first_name'] ?? null,
        );
    }
}
