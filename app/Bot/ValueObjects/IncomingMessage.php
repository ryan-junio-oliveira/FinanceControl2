<?php

namespace App\Bot\ValueObjects;

/** Mensagem normalizada, independente do canal. */
final class IncomingMessage
{
    public function __construct(
        public readonly string $channel,
        public readonly string $chatId,
        public readonly string $text,
        public readonly ?string $fromName = null,
    ) {}
}
