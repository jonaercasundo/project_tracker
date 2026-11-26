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
    'apify' => [
        'token' => env('APIFY_API_TOKEN'),
    ],
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'research_model' => env('ANTHROPIC_RESEARCH_MODEL', 'claude-sonnet-5-5'),
        'research_models' => ['claude-sonnet-5-5', 'claude-haiku-5-5', 'claude-opus-5-5'],
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'assistant_model' => env('OPENAI_ASSISTANT_MODEL', 'gpt-5.5'),
        'assistant_timeout' => env('OPENAI_ASSISTANT_TIMEOUT', 90),
    ],
    'mmc_platform' => [
        'base_url' => env('MMC_PLATFORM_API_BASE_URL'),
        'read_key' => env('MMC_PLATFORM_READ_KEY'),
        'report_programs' => [
            502698 => '989d848b-20b5-4074-b315-ced79af14c91',
        ],
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
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

];
