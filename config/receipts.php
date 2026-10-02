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
        'binary' => env('TESSERACT_BIN', base_path('bin/tesseract/tesseract.exe')),
        'tessdata_dir' => env('TESSDATA_DIR', base_path('bin/tesseract/tessdata')),
        'lang' => 'por',
    ],

    /** Mínimo de caracteres para aceitar texto extraído de PDF. */
    'pdf_min_chars' => 50,
];
