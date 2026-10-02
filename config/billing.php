<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Assinaturas (Mercado Pago)
    |--------------------------------------------------------------------------
    | Ligado/desligado via env. Enquanto BILLING_ENABLED=false, o trial de 14
    | dias não é exigido e o sistema funciona sem bloqueio.
    */
    'enabled' => (bool) env('BILLING_ENABLED', false),

    'mercado_pago' => [
        'base_url' => env('MERCADOPAGO_API', 'https://api.mercadopago.com'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
        // URL de retorno após pagar no MP (colocada no checkout).
        'back_url' => env('MERCADOPAGO_BACK_URL', env('APP_URL', 'http://localhost').'/plans'),
        // Em modo de teste (conta MP de testes), o comprador também precisa
        // ser um test user. Setar o email aqui para simular o pagamento.
        'test_payer_email' => env('MERCADOPAGO_TEST_PAYER_EMAIL', null),
        // ID fixo do plano criado no MP (criar com BillingService::ensurePlan).
        'preapproval_plan_id' => env('MERCADOPAGO_PLAN_ID', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Planos oferecidos
    |--------------------------------------------------------------------------
    | frequencia em meses (1 = mensal, 12 = anual).
    */
    'plans' => [
        'mensal' => [
            'label' => 'Mensal',
            'price' => (float) env('PLAN_MENSAL_PRICE', 1.00),
            'frequencia' => 1,
            'tipo' => 'months',
            'destaque' => false,
        ],
        'anual' => [
            'label' => 'Anual',
            'price' => (float) env('PLAN_ANUAL_PRICE', 10.00),
            'frequencia' => 12,
            'tipo' => 'months',
            'destaque' => true,
        ],
    ],

    // Trial em dias concedido no cadastro.
    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 14),
];
