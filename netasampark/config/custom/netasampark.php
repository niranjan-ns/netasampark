<?php

return [
    'app' => [
        'name' => env('APP_NAME', 'NetaSampark - Political CRM'),
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'production'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
        'locale' => env('APP_LOCALE', 'en'),
        'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    ],

    'organization' => [
        'max_users' => [
            'starter' => 10,
            'pro' => 50,
            'enterprise' => 500,
        ],
        'max_voters' => [
            'starter' => 10000,
            'pro' => 100000,
            'enterprise' => 1000000,
        ],
        'max_campaigns' => [
            'starter' => 5,
            'pro' => 25,
            'enterprise' => 100,
        ],
        'trial_days' => 14,
        'grace_period_days' => 7,
    ],

    'messaging' => [
        'whatsapp' => [
            'enabled' => env('WHATSAPP_API_KEY') !== null,
            'api_key' => env('WHATSAPP_API_KEY'),
            'rate_limit' => env('RATE_LIMIT_WHATSAPP', 1000),
        ],
        'sms' => [
            'enabled' => env('SMS_GATEWAY_KEY') !== null,
            'gateway' => env('SMS_GATEWAY', 'msg91'),
            'rate_limit' => env('RATE_LIMIT_SMS', 100),
        ],
    ],

    'compliance' => [
        'trai_dlt' => [
            'enabled' => true,
            'template_id' => env('TRAI_DLT_TEMPLATE_ID'),
            'entity_id' => env('TRAI_DLT_ENTITY_ID'),
        ],
        'election_commission' => [
            'enabled' => env('EC_COMPLIANCE_ENABLED', true),
            'expense_limits' => [
                'parliamentary' => 95000000,
                'assembly' => 28000000,
                'municipal' => 2000000,
                'panchayat' => 500000,
            ],
        ],
    ],

    'features' => [
        'ai_insights' => env('FEATURE_AI_INSIGHTS', true),
        'voice_dialer' => env('FEATURE_VOICE_DIALER', true),
        'offline_sync' => env('FEATURE_OFFLINE_SYNC', true),
    ],
];
