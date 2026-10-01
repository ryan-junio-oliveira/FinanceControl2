<?php

namespace App\Bot\Drivers;

use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use Illuminate\Http\Request;

/**
 * Driver nulo: não envia nada, só registra (testes e dev sem rede).
 * No futuro, o driver do WhatsApp implementa o mesmo contrato.
 */
final class NullDriver implements BotDriver
{
    /** @var array<int, array{chat: string, text: string}> */
    public static array $sent = [];

    /** Caminho local a devolver no download (definido pelo teste). */
    public static ?string $fixturePath = null;

    public function name(): string
    {
        return 'null';
    }

    public function sendText(string $chatId, string $text, ?BotKeyboard $keyboard = null): void
    {
        self::$sent[] = ['chat' => $chatId, 'text' => $text];
    }

    public function downloadFile(string $fileId): ?string
    {
        return self::$fixturePath;
    }

    public function parseWebhook(Request $request): ?IncomingMessage
    {
        $data = $request->json()->all() + $request->all();

        // Formato simples do próprio NullDriver.
        if (! empty($data['chat_id']) && isset($data['text'])) {
            return new IncomingMessage('null', (string) $data['chat_id'], (string) $data['text']);
        }

        // Formato do Telegram (para simular o canal nos testes).
        if (isset($data['callback_query'])) {
            $cb = $data['callback_query'];
            $chatId = (string) ($cb['message']['chat']['id'] ?? $cb['from']['id'] ?? '');
            if ($chatId === '') {
                return null;
            }

            return new IncomingMessage('telegram', $chatId, (string) ($cb['data'] ?? ''));
        }
        $msg = $data['message'] ?? null;
        if (! is_array($msg) || ! isset($msg['chat']['id'])) {
            return null;
        }

        $fileId = null;
        $fileKind = null;
        $fileMime = null;
        $fileName = null;
        if (! empty($msg['photo']) && is_array($msg['photo'])) {
            $biggest = collect($msg['photo'])->sortByDesc(fn ($p) => ($p['file_size'] ?? 0))->first();
            $fileId = $biggest['file_id'] ?? null;
            $fileKind = $fileId ? 'photo' : null;
            $fileMime = 'image/jpeg';
        } elseif (! empty($msg['document']['file_id'])) {
            $fileId = $msg['document']['file_id'];
            $fileKind = 'document';
            $fileMime = $msg['document']['mime_type'] ?? null;
            $fileName = $msg['document']['file_name'] ?? null;
        }

        $text = trim((string) ($msg['text'] ?? ($msg['caption'] ?? '')));
        if ($text === '' && $fileId === null) {
            return null;
        }

        return new IncomingMessage(
            'telegram',
            (string) $msg['chat']['id'],
            $text,
            $msg['from']['first_name'] ?? null,
            $fileId,
            $fileKind,
            $fileMime,
            $fileName,
        );
    }

    public static function flush(): void
    {
        self::$sent = [];
    }

    public static function lastText(): ?string
    {
        $last = end(self::$sent);

        return $last ? $last['text'] : null;
    }
}
