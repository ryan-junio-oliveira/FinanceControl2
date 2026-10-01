<?php

namespace App\Bot\Drivers;

use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $inline = [];
            foreach ($keyboard->rows as $row) {
                $buttons = [];
                foreach ((array) $row as $btn) {
                    if (! is_array($btn) || ! isset($btn[0]) || ! isset($btn[1])) {
                        continue;
                    }
                    $buttons[] = ['text' => (string) $btn[0], 'callback_data' => mb_substr((string) $btn[1], 0, 64)];
                }
                if ($buttons !== []) {
                    $inline[] = $buttons;
                }
            }
            if ($inline !== []) {
                $payload['reply_markup'] = ['inline_keyboard' => $inline];
            }
        }

        try {
            Http::baseUrl(config('bot.telegram.api')."/bot{$token}")
                ->timeout(10)
                ->post('/sendMessage', $payload)
                ->throw();
        } catch (\Throwable $e) {
            Log::error('[bot] Falha ao enviar Telegram: '.$e->getMessage());
        }
    }

    public function downloadFile(string $fileId): ?string
    {
        $token = (string) config('bot.telegram.token');
        if ($token === '') {
            return null;
        }

        try {
            $info = Http::baseUrl(config('bot.telegram.api')."/bot{$token}")
                ->timeout(15)
                ->get('/getFile', ['file_id' => $fileId]);
            $path = $info->json('result.file_path');
            if (! $path) {
                return null;
            }
            $bytes = Http::baseUrl(config('bot.telegram.api'))
                ->timeout(30)
                ->get("/file/bot{$token}/{$path}");
            if (! $bytes->successful()) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'bin');
            $local = 'receipts/'.date('Y/m').'/'.uniqid('rcp_', true).'.'.$ext;
            Storage::disk('local')->put($local, $bytes->body());

            return Storage::disk('local')->path($local);
        } catch (\Throwable $e) {
            Log::error('[bot] Falha ao baixar arquivo: '.$e->getMessage());

            return null;
        }
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

        // Mensagem de texto, foto ou documento.
        $msg = $update['message'] ?? null;
        if (! is_array($msg) || ! isset($msg['chat']['id'])) {
            return null;
        }

        // Foto (pega a maior resolução) ou documento (PDF etc.).
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
            channel: 'telegram',
            chatId: (string) $msg['chat']['id'],
            text: $text,
            fromName: $msg['from']['first_name'] ?? null,
            fileId: $fileId,
            fileKind: $fileKind,
            fileMime: $fileMime,
            fileName: $fileName,
        );
    }
}
