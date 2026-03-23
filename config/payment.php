<?php

return [
    'default_currency' => env('PAYMENT_CURRENCY', 'MVR'),

    'gateways' => [
        'bml' => [
            'merchant_id' => env('BML_MERCHANT_ID'),
            'api_key' => env('BML_API_KEY'),
            'api_secret' => env('BML_API_SECRET'),
            'sandbox' => env('BML_SANDBOX', true),
        ],
    ],

    'supported_methods' => [
        'bml_gateway',
        'bank_transfer',
        'ooredoo_mfaisaa',
        'dhiraagu_pay',
    ],
];
