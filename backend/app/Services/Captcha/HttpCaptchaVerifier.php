<?php

declare(strict_types=1);

namespace App\Services\Captcha;

use App\Contracts\CaptchaVerifier;
use App\ValueObjects\CaptchaProviderConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class HttpCaptchaVerifier implements CaptchaVerifier
{
    public function __construct(
        private CaptchaProviderConfig $provider,
        private float $timeout,
    ) {}

    public function verify(string $token, ?string $remoteIp): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->post($this->provider->verifyUrl, [
                    'secret' => $this->provider->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp ?? '',
                ]);

            return $response->successful() && $response->json('success') === true;
        } catch (Throwable $exception) {
            Log::error('Erro ao verificar CAPTCHA.', [
                'provider' => $this->provider->name->value,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
