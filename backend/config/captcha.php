<?php

return [
    'enabled' => filter_var(env('CAPTCHA_ENABLED', true), FILTER_VALIDATE_BOOL),
    'providers' => env('CAPTCHA_PROVIDERS', 'turnstile'),
    'timeout' => (float) env('CAPTCHA_TIMEOUT_SECONDS', 5),
    'definitions' => [
        'turnstile' => [
            'site_key' => env('TURNSTILE_SITE_KEY', ''),
            'secret' => 'TURNSTILE_SECRET_KEY',
            'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        ],
        'recaptcha' => [
            'site_key' => env('RECAPTCHA_SITE_KEY', ''),
            'secret' => 'RECAPTCHA_SECRET_KEY',
            'verify_url' => env('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),
        ],
        'hcaptcha' => [
            'site_key' => env('HCAPTCHA_SITE_KEY', ''),
            'secret' => 'HCAPTCHA_SECRET_KEY',
            'verify_url' => env('HCAPTCHA_VERIFY_URL', 'https://hcaptcha.com/siteverify'),
        ],
    ],
];
