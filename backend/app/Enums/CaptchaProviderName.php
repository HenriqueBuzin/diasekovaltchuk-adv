<?php

declare(strict_types=1);

namespace App\Enums;

enum CaptchaProviderName: string
{
    case Turnstile = 'turnstile';
    case Recaptcha = 'recaptcha';
    case Hcaptcha = 'hcaptcha';

    public static function normalize(string $value): ?self
    {
        $normalized = trim($value);
        $normalized = str_replace(['-', '_'], '', $normalized);
        $normalized = strtolower($normalized);
        $normalized = match ($normalized) {
            'cloudflare', 'cloudflareturnstile' => 'turnstile',
            'google' => 'recaptcha',
            default => $normalized,
        };

        return self::tryFrom($normalized);
    }
}
