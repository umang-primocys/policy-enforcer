<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed URIs
    |--------------------------------------------------------------------------
    | These routes will bypass token verification.
    */
    'allowed_uris' => [
        'login',
        'register',
        'health-check',
    ],

    'enabled' => env('POLICY_ENFORCER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Token Verification API URL
    |--------------------------------------------------------------------------
    */
    'verify_url' => env('POLICY_VERIFY_URL', 'https://your-central-server.com/api/verify'),
];
