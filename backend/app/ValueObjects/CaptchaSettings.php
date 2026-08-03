<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class CaptchaSettings
{
    /** @param list<CaptchaProviderConfig> $providers */
    public function __construct(
        public bool $enabled,
        public array $providers,
        public float $timeout,
    ) {}

    public function defaultProvider(): ?CaptchaProviderConfig
    {
        return $this->providers[0] ?? null;
    }
}
