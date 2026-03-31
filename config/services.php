<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'prices' => [
            'starter' => env('STRIPE_PRICE_STARTER'),
            'professional' => env('STRIPE_PRICE_PROFESSIONAL'),
            'business' => env('STRIPE_PRICE_BUSINESS'),
            'enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
        ],
    ],

    'paymongo' => [
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'api_base' => env('PAYMONGO_API_BASE', 'https://api.paymongo.com/v1'),
        'payment_method_types' => array_values(array_filter(array_map(
            static fn (string $methodType): string => trim($methodType),
            explode(',', (string) env('PAYMONGO_PAYMENT_METHOD_TYPES', 'gcash,paymaya,grab_pay,card'))
        ))),
        'webhook' => [
            'token' => env('PAYMONGO_WEBHOOK_TOKEN'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
