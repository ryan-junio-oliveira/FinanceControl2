<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Driver do bot (Strategy trocável)
    |--------------------------------------------------------------------------
    | 'telegram' usa a Bot API do Telegram (grátis e ilimitada).
    | 'whatsapp' usará a Cloud API (respostas em janela de 24h são grátis).
    | 'null' não envia nada (testes/dev sem rede).
    */
    'driver' => env('BOT_DRIVER', 'null'),

    'telegram' => [
        'token' => env('BOT_TELEGRAM_TOKEN'),
        'secret' => env('BOT_TELEGRAM_SECRET'),
        'api' => 'https://api.telegram.org',
    ],

    'whatsapp' => [
        'token' => env('BOT_WHATSAPP_TOKEN'),
        'phone_id' => env('BOT_WHATSAPP_PHONE_ID'),
        'api' => 'https://graph.facebook.com/v21.0',
    ],

    /** Minutos sem interação até a conversa expirar. */
    'state_ttl' => (int) env('BOT_STATE_TTL', 30),
];
