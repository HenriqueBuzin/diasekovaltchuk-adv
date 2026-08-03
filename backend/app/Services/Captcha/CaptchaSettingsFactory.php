<?php

declare(strict_types=1);

namespace App\Services\Captcha;

use App\Enums\CaptchaProviderName;
use App\Support\SecretReader;
use App\ValueObjects\CaptchaProviderConfig;
use App\ValueObjects\CaptchaSettings;
use RuntimeException;

final class CaptchaSettingsFactory
{
    public function make(): CaptchaSettings
    {
        $enabled = (bool) config('captcha.enabled', true);
        $timeoutValue = config('captcha.timeout', 5);
        if (! is_float($timeoutValue) && ! is_int($timeoutValue) && ! is_string($timeoutValue)) {
            throw new RuntimeException(trans('site.invalid_captcha_timeout'));
        }
        if (! is_numeric($timeoutValue)) {
            throw new RuntimeException(trans('site.invalid_captcha_timeout'));
        }
        $timeout = (float) $timeoutValue;
        if (! $enabled) {
            return new CaptchaSettings(false, [], $timeout);
        }

        $providers = [];
        foreach ($this->providerNames() as $name) {
            $definition = config("captcha.definitions.{$name->value}");
            if (! is_array($definition)) {
                throw new RuntimeException(trans('site.unsupported_captcha_provider', ['name' => $name->value]));
            }

            $siteKey = $definition['site_key'] ?? '';
            $secretName = $definition['secret'] ?? '';
            $verifyUrl = $definition['verify_url'] ?? '';
            $siteKey = $this->requiredText(
                $siteKey,
                trans('site.missing_env', ['name' => strtoupper($name->value).'_SITE_KEY']),
            );
            $secretName = $this->requiredText(
                $secretName,
                trans('site.unsupported_captcha_provider', ['name' => $name->value]),
            );
            $verifyUrl = $this->requiredText(
                $verifyUrl,
                trans('site.unsupported_captcha_provider', ['name' => $name->value]),
            );

            $providers[] = new CaptchaProviderConfig(
                $name,
                $siteKey,
                SecretReader::read($secretName),
                $verifyUrl,
            );
        }

        return new CaptchaSettings(true, $providers, $timeout);
    }

    /** @return list<CaptchaProviderName> */
    private function providerNames(): array
    {
        $configured = config('captcha.providers', 'turnstile');
        if (! is_string($configured)) {
            throw new RuntimeException(trans('site.invalid_captcha_providers'));
        }
        $names = [];
        foreach (explode(',', $configured) as $rawName) {
            $rawName = trim($rawName);
            if ($rawName === '') {
                continue;
            }
            $name = CaptchaProviderName::normalize($rawName);
            if ($name === null) {
                throw new RuntimeException(trans('site.unsupported_captcha_provider', ['name' => $rawName]));
            }
            $names[$name->value] = $name;
        }

        return array_values($names);
    }

    private function requiredText(mixed $value, string $message): string
    {
        if (! is_string($value)) {
            throw new RuntimeException($message);
        }

        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }
}
