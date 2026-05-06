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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'midtrans' => [
        'enabled' => env('MIDTRANS_ENABLED', false),
        'mode' => env('MIDTRANS_MODE', env('MIDTRANS_IS_SANDBOX', true) ? 'sandbox' : 'live'),
        'is_sandbox' => env('MIDTRANS_IS_SANDBOX', true),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'base_url_sandbox' => env('MIDTRANS_BASE_URL_SANDBOX', 'https://api.sandbox.midtrans.com'),
        'base_url_live' => env('MIDTRANS_BASE_URL_LIVE', 'https://api.midtrans.com'),
    ],

    'whatsapp' => [
        'enabled' => env('WA_ENABLED', false),
        'api_url' => env('WA_API_URL', 'https://api.fonnte.com/send'),
        'api_token' => env('WA_API_TOKEN'),
        'auth_header' => env('WA_AUTH_HEADER', 'Authorization'),
        'notify_checkout' => env('WA_NOTIFY_CHECKOUT', true),
        'notify_store_target' => env('WA_NOTIFY_STORE_TARGET', true),
        'notify_customer_target' => env('WA_NOTIFY_CUSTOMER_TARGET', false),
    ],

];
