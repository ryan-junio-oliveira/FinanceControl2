<?php

namespace App\Receipts;

use App\Receipts\Contracts\ReceiptReader;
use App\Receipts\Readers\TesseractReader;

/** Factory do leitor (troca via RECEIPT_READER). */
final class ReceiptManager
{
    public static function reader(?string $name = null): ReceiptReader
    {
        // Permite sobrescrever no container (testes, multi-tenant).
        if (app()->bound(ReceiptReader::class)) {
            return app(ReceiptReader::class);
        }

        return match ($name ?? (string) config('receipts.reader', 'tesseract')) {
            default => app(TesseractReader::class),
        };
    }
}
