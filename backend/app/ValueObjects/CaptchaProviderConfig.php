<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\CaptchaProviderName;

final readonly class CaptchaProviderConfig
{
    public function __construct(
        public CaptchaProviderName $name,
        public string $siteKey,
        public string $secretKey,
        public string $verifyUrl,
    ) {}

    /** @return array{name: string, siteKey: string} */
    public function publicData(): array
    {
        return ['name' => $this->name->value, 'siteKey' => $this->siteKey];
    }
}
