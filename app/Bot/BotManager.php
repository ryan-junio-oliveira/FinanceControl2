<?php

namespace App\Bot;

use App\Bot\Contracts\BotDriver;
use App\Bot\Drivers\NullDriver;
use App\Bot\Drivers\TelegramDriver;

/**
 * Factory do driver (troca de canal = 1 variável de ambiente).
 */
final class BotManager
{
    public static function driver(?string $name = null): BotDriver
    {
        return match ($name ?? (string) config('bot.driver', 'null')) {
            'telegram' => app(TelegramDriver::class),
            default => app(NullDriver::class),
            // 'whatsapp' => app(WhatsAppDriver::class), // futuro
        };
    }
}
