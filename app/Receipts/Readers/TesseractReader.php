<?php

namespace App\Receipts\Readers;

use App\Receipts\Contracts\ReceiptReader;
use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * OCR local com Tesseract (grátis/offline).
 *
 * - Imagens: OCR direto em português.
 * - PDFs: extrai o texto embarcado (comprovantes digitais têm);
 *   PDF escaneado (só imagem) não é suportado sem rasterizador.
 */
final class TesseractReader implements ReceiptReader
{
    public function name(): string
    {
        return 'tesseract';
    }

    public function supports(string $mime): bool
    {
        return str_starts_with($mime, 'image/')
            || in_array($mime, ['application/pdf'], true);
    }

    public function read(string $path): string
    {
        $mime = (string) mime_content_type($path);
        if (str_starts_with($mime, 'image/')) {
            return $this->ocr($path);
        }
        if ($mime === 'application/pdf') {
            return $this->pdfText($path);
        }

        return '';
    }

    private function ocr(string $path): string
    {
        try {
            $tessdata = (string) config('receipts.tesseract.tessdata_dir');
            if ($tessdata !== '') {
                putenv('TESSDATA_PREFIX='.$tessdata);
            }
            $text = (new TesseractOCR($path))
                ->executable((string) config('receipts.tesseract.binary', 'tesseract'))
                ->lang((string) config('receipts.tesseract.lang', 'por'))
                ->psm(6)
                ->run();
        } catch (\Throwable) {
            return '';
        }

        return trim($text);
    }

    private function pdfText(string $path): string
    {
        try {
            $text = trim((new PdfParser)->parseFile($path)->getText());
        } catch (\Throwable) {
            return '';
        }

        return mb_strlen($text) >= (int) config('receipts.pdf_min_chars', 50) ? $text : '';
    }
}
