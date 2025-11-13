<?php

return [
    'expedia' => [
        'key' => env('EXPEDIA_API_KEY', 'demo-key'),
        'shared_secret' => env('EXPEDIA_SHARED_SECRET', 'demo-secret'),
        'base_url' => env('EXPEDIA_BASE_URL', 'https://test.ean.com/v3'),
        'user_agent' => env('EXPEDIA_USER_AGENT', 'ExpediaLaravelDemo/1.0'),
    ],
];
