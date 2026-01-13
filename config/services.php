<?php

return [
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

    'sejoli' => [
        'base_url' => env('SEJOLI_API_URL', env('SEJOLI_BASE_URL', 'https://kavling.pro')),
        'api_key' => env('SEJOLI_API_KEY'),
        'webhook_secret' => env('SEJOLI_WEBHOOK_SECRET'),
        'upgrade_url' => env('SEJOLI_UPGRADE_URL'),
        'addon_url' => env('SEJOLI_ADDON_URL'),
    ],
];
