<?php

return [
    'contact_email' => env('CONTACT_EMAIL', ''),
    'contact_to' => env('CONTACT_TO', ''),
    'whats_number' => env('WHATS_NUMBER', ''),
    'social_facebook' => env('SOCIAL_FB_URL', ''),
    'social_instagram' => env('SOCIAL_IG_URL', ''),
    'email_dns_validation' => filter_var(env('EMAIL_DNS_VALIDATION_ENABLED', true), FILTER_VALIDATE_BOOL),
    'swagger_enabled' => filter_var(env('SWAGGER_ENABLED', false), FILTER_VALIDATE_BOOL),
    'field_limits' => [
        'nome' => ['min' => 3, 'max' => 120],
        'email' => ['min' => 3, 'max' => 160],
        'telefone' => ['min' => 10, 'max' => 11],
        'assunto' => ['min' => 3, 'max' => 160],
        'mensagem' => ['min' => 10, 'max' => 1200],
    ],
];
