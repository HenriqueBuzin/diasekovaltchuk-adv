<?php

declare(strict_types=1);

namespace App\Services\Captcha;

use App\Enums\CaptchaProviderName;
use App\ValueObjects\CaptchaProviderConfig;

final readonly class CaptchaOrchestrator
{
    public function __construct(private CaptchaSettingsFactory $settingsFactory) {}

    public function verify(?string $providerName, string $token, ?string $remoteIp): bool
    {
        $settings = $this->settingsFactory->make();
        if (! $settings->enabled) {
            return true;
        }

        $requested = CaptchaProviderName::normalize($providerName ?? '');
        $provider = $requested === null && count($settings->providers) === 1
            ? $settings->defaultProvider()
            : $this->findProvider($settings->providers, $requested);

        return $provider !== null
            && (new HttpCaptchaVerifier($provider, $settings->timeout))->verify($token, $remoteIp);
    }

    /**
     * @param  list<CaptchaProviderConfig>  $providers
     */
    private function findProvider(array $providers, ?CaptchaProviderName $requested): ?CaptchaProviderConfig
    {
        foreach ($providers as $provider) {
            if ($provider->name === $requested) {
                return $provider;
            }
        }

        return null;
    }
}
