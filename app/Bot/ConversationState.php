<?php

namespace App\Bot;

use Illuminate\Support\Facades\Cache;

/**
 * Estado da conversa (máquina de estados dos fluxos multi-etapa).
 * Chave por canal+chat; expira após inatividade (config bot.state_ttl).
 */
final class ConversationState
{
    public static function key(string $channel, string $chatId): string
    {
        return "bot:state:{$channel}:{$chatId}";
    }

    /** @return array{handler: string, step: string, data: array}|null */
    public static function get(string $channel, string $chatId): ?array
    {
        $state = Cache::get(self::key($channel, $chatId));

        return is_array($state) ? $state : null;
    }

    public static function put(string $channel, string $chatId, string $handler, string $step, array $data = []): void
    {
        Cache::put(
            self::key($channel, $chatId),
            ['handler' => $handler, 'step' => $step, 'data' => $data],
            now()->addMinutes((int) config('bot.state_ttl', 30))
        );
    }

    public static function clear(string $channel, string $chatId): void
    {
        Cache::forget(self::key($channel, $chatId));
    }
}
