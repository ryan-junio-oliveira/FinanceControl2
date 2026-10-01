<?php

namespace App\Receipts\Contracts;

/**
 * Lê texto de um comprovante (imagem ou PDF).
 * Trocável como o driver do bot: Tesseract local hoje, IA com visão no futuro.
 */
interface ReceiptReader
{
    public function name(): string;

    public function supports(string $mime): bool;

    /** Texto bruto extraído. String vazia = ilegível/não suportado. */
    public function read(string $path): string;
}
