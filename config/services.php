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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'google' => [
        'client_id' => '1092489788909-5gne8npgbnncqcs38882t3v1c8igbir9.apps.googleusercontent.com',
        'client_secret' => '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',
         /* Live Call back Url  */
         'redirect' => 'https://fixerity.com/auth/google/callback',

        /* Staging Call back Url  */
//        'redirect' => 'https://staging-fox-handyman.startuptrinity.com/auth/google/callback',
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'app_sid' => env('TWILIO_APP_SID'),
        'push_credential_sid' => env('TWILIO_PUSH_CREDENTIAL_SID'),
        'caller_number' => env('TWILIO_CALLER_NUMBER', '+911234567890'),
    ],

    'agent_webhook' => [
        'url' => env('AGENT_SERVICE_WEBHOOK_URL'),
        'secret' => env('AGENT_WEBHOOK_SECRET'),
    ],

];
