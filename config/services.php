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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google_sheets' => [
        'default_channel' => env('GOOGLE_SHEETS_DEFAULT_CHANNEL', 'telegram'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'cache_ttl' => env('GOOGLE_SHEETS_CACHE_TTL', 55),
        'channels' => [
            'telegram' => [
                'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID_TELEGRAM', env('GOOGLE_SHEETS_SPREADSHEET_ID')),
                'completed_range' => env('GOOGLE_SHEETS_TELEGRAM_COMPLETED_RANGE', env('GOOGLE_SHEETS_COMPLETED_RANGE', 'Completed Orders!A1:Z9999')),
                'abandoned_range' => env('GOOGLE_SHEETS_TELEGRAM_ABANDONED_RANGE', env('GOOGLE_SHEETS_ABANDONED_RANGE', 'Abandoned!A1:Z9999')),
            ],
            'whatsapp' => [
                'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID_WHATSAPP'),
                'completed_range' => env('GOOGLE_SHEETS_WHATSAPP_COMPLETED_RANGE', env('GOOGLE_SHEETS_COMPLETED_RANGE', 'Completed Orders!A1:Z9999')),
                'abandoned_range' => env('GOOGLE_SHEETS_WHATSAPP_ABANDONED_RANGE', env('GOOGLE_SHEETS_ABANDONED_RANGE', 'Abandoned!A1:Z9999')),
            ],
            'voice' => [
                'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID_VOICE'),
                'completed_range' => env('GOOGLE_SHEETS_VOICE_COMPLETED_RANGE', env('GOOGLE_SHEETS_COMPLETED_RANGE', 'Completed Orders!A1:Z9999')),
                'abandoned_range' => env('GOOGLE_SHEETS_VOICE_ABANDONED_RANGE', env('GOOGLE_SHEETS_ABANDONED_RANGE', 'Abandoned!A1:Z9999')),
            ],
        ],
    ],

    'webhooks' => [
        'dispatch' => [
            'url' => env('WEBHOOK_DISPATCH_URL'),
            'chat_id' => env('WEBHOOK_DISPATCH_CHAT_ID', '7948113920'),
        ],
    ],

];
