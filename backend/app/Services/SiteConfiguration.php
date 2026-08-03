<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Captcha\CaptchaSettingsFactory;
use App\Support\SecretReader;
use RuntimeException;

final readonly class SiteConfiguration
{
    public function __construct(private CaptchaSettingsFactory $captchaSettings) {}

    /** @return array<string, mixed> */
    public function publicData(): array
    {
        $captcha = $this->captchaSettings->make();
        $whatsNumber = $this->required('whats_number', 'WHATS_NUMBER');
        SecretReader::applicationKey();
        SecretReader::read('MAIL_PASSWORD');

        return [
            'contactEmail' => $this->required('contact_email', 'CONTACT_EMAIL'),
            'whatsNumber' => $whatsNumber,
            'whatsLinkNumber' => preg_replace('/\D/', '', $whatsNumber) ?? '',
            'socialFacebook' => $this->required('social_facebook', 'SOCIAL_FB_URL'),
            'socialInstagram' => $this->required('social_instagram', 'SOCIAL_IG_URL'),
            'captchaEnabled' => $captcha->enabled,
            'captchaProviders' => array_map(
                static fn ($provider): array => $provider->publicData(),
                $captcha->providers,
            ),
            'turnstileSiteKey' => $captcha->providers[0]->siteKey ?? '',
            'fieldLimits' => config('site.field_limits'),
        ];
    }

    /** @return list<string> */
    public function recipients(): array
    {
        $raw = $this->required('contact_to', 'CONTACT_TO');
        $recipients = array_values(array_filter(array_map('trim', explode(',', $raw))));
        if ($recipients === []) {
            throw new RuntimeException(trans('site.missing_contact_to'));
        }

        return $recipients;
    }

    private function required(string $key, string $environment): string
    {
        $value = config("site.{$key}");
        if (! is_string($value)) {
            throw new RuntimeException(trans('site.missing_env', ['name' => $environment]));
        }

        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException(trans('site.missing_env', ['name' => $environment]));
        }

        return $value;
    }
}
