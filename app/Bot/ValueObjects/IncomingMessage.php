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
        public readonly ?string $fileId = null,
        public readonly ?string $fileKind = null, // 'photo'|'document'
        public readonly ?string $fileMime = null,
        public readonly ?string $fileName = null,
    ) {}

    public function hasFile(): bool
    {
        return $this->fileId !== null;
    }
}
