<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Leitor de comprovantes (trocável como o driver do bot)
    |--------------------------------------------------------------------------
    | 'tesseract' usa OCR local (grátis/offline). No futuro, um leitor
    | com visão por IA pode ser plugado no mesmo contrato.
    */
    'reader' => env('RECEIPT_READER', 'tesseract'),

    'tesseract' => [
        // Windows: usa o binário portátil do repo. Linux: usa o tesseract do sistema.
        'binary' => env('TESSERACT_BIN', PHP_OS_FAMILY === 'Windows' ? base_path('bin/tesseract/tesseract.exe') : 'tesseract'),
        'tessdata_dir' => env('TESSDATA_DIR', PHP_OS_FAMILY === 'Windows' ? base_path('bin/tesseract/tessdata') : ''),
        'lang' => 'por',
    ],

    /** Mínimo de caracteres para aceitar texto extraído de PDF. */
    'pdf_min_chars' => 50,
];
