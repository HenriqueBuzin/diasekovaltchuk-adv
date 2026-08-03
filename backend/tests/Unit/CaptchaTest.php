<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\CaptchaProviderName;
use App\Services\Captcha\CaptchaOrchestrator;
use App\Services\Captcha\CaptchaSettingsFactory;
use App\Services\Captcha\HttpCaptchaVerifier;
use App\ValueObjects\CaptchaProviderConfig;
use App\ValueObjects\CaptchaSettings;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class CaptchaTest extends TestCase
{
    public function test_normalizes_provider_names_and_value_objects(): void
    {
        self::assertSame(CaptchaProviderName::Turnstile, CaptchaProviderName::normalize('Cloudflare-Turnstile'));
        self::assertSame(CaptchaProviderName::Turnstile, CaptchaProviderName::normalize('cloudflare'));
        self::assertSame(CaptchaProviderName::Recaptcha, CaptchaProviderName::normalize('google'));
        self::assertSame(CaptchaProviderName::Recaptcha, CaptchaProviderName::normalize('recaptcha'));
        self::assertSame(CaptchaProviderName::Hcaptcha, CaptchaProviderName::normalize('h_captcha'));
        self::assertSame(CaptchaProviderName::Hcaptcha, CaptchaProviderName::normalize('hcaptcha'));
        self::assertSame(CaptchaProviderName::Turnstile, CaptchaProviderName::normalize('turnstile'));
        self::assertSame(CaptchaProviderName::Turnstile, CaptchaProviderName::normalize(' TURNSTILE '));
        self::assertNull(CaptchaProviderName::normalize('unknown'));

        $provider = $this->provider();
        self::assertSame(['name' => 'turnstile', 'siteKey' => 'site-key'], $provider->publicData());
        self::assertSame($provider, (new CaptchaSettings(true, [$provider], 5))->defaultProvider());
        self::assertNull((new CaptchaSettings(false, [], 5))->defaultProvider());
    }

    public function test_builds_disabled_and_multi_provider_settings(): void
    {
        $factory = app(CaptchaSettingsFactory::class);
        $this->configure(['captcha.enabled' => false, 'captcha.timeout' => 2.5]);
        $disabled = $factory->make();
        self::assertFalse($disabled->enabled);
        self::assertSame([], $disabled->providers);

        $this->configure(['captcha.enabled' => false, 'captcha.timeout' => 2]);
        self::assertSame(2.0, $factory->make()->timeout);

        $this->configure([
            'captcha.enabled' => true,
            'captcha.timeout' => '2.5',
            'captcha.providers' => ' turnstile ,,google,hcaptcha,turnstile,',
            'captcha.definitions.turnstile' => [
                'site_key' => ' turnstile-site ',
                'secret' => 'TURNSTILE_SECRET_KEY',
                'verify_url' => ' https://captcha.test ',
            ],
            'captcha.definitions.recaptcha' => $this->definition('RECAPTCHA_SECRET_KEY', 'recaptcha-site'),
            'captcha.definitions.hcaptcha' => $this->definition('HCAPTCHA_SECRET_KEY', 'hcaptcha-site'),
        ]);
        $settings = $factory->make();
        self::assertSame(2.5, $settings->timeout);
        self::assertSame(['turnstile', 'recaptcha', 'hcaptcha'], array_map(
            static fn (CaptchaProviderConfig $provider): string => $provider->name->value,
            $settings->providers,
        ));
    }

    public function test_rejects_invalid_captcha_configuration(): void
    {
        $factory = app(CaptchaSettingsFactory::class);
        $cases = [
            [['captcha.enabled' => true, 'captcha.timeout' => new \stdClass], 'numérico'],
            [['captcha.enabled' => true, 'captcha.timeout' => 'invalid'], 'numérico'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => []], 'lista'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'unknown'], 'unknown'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => null], 'turnstile'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => $this->definition('TURNSTILE_SECRET_KEY', '')], 'SITE_KEY'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => ['site_key' => [], 'secret' => 'TURNSTILE_SECRET_KEY', 'verify_url' => 'url']], 'SITE_KEY'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => ['site_key' => 'site', 'secret' => [], 'verify_url' => 'url']], 'turnstile'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => ['site_key' => 'site', 'secret' => '', 'verify_url' => 'url']], 'turnstile'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => ['site_key' => 'site', 'secret' => 'TURNSTILE_SECRET_KEY', 'verify_url' => []]], 'turnstile'],
            [['captcha.enabled' => true, 'captcha.timeout' => 5, 'captcha.providers' => 'turnstile', 'captcha.definitions.turnstile' => ['site_key' => 'site', 'secret' => 'TURNSTILE_SECRET_KEY', 'verify_url' => '']], 'turnstile'],
        ];

        foreach ($cases as [$configuration, $expected]) {
            $this->configure($configuration);
            try {
                $factory->make();
                self::fail('A configuração inválida deveria falhar.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString($expected, $exception->getMessage());
            }
        }
    }

    public function test_http_verifier_handles_empty_success_failure_and_exception(): void
    {
        $verifier = new HttpCaptchaVerifier($this->provider(), 3);
        self::assertFalse($verifier->verify('', '127.0.0.1'));

        Http::fake(['captcha.test' => Http::sequence()
            ->push(['success' => true], 200)
            ->push(['success' => false], 200)
            ->push(['success' => true], 500)]);
        self::assertTrue($verifier->verify('token', null));
        Http::assertSent(fn ($request): bool => $request['remoteip'] === '' && $request['secret'] === 'secret-key');

        self::assertFalse($verifier->verify('token', '203.0.113.10'));

        self::assertFalse($verifier->verify('token', '127.0.0.1'));
    }

    public function test_http_verifier_handles_transport_exceptions(): void
    {
        Http::fake(static function (): never {
            throw new RuntimeException('network');
        });
        self::assertFalse((new HttpCaptchaVerifier(new CaptchaProviderConfig(
            CaptchaProviderName::Turnstile,
            'site',
            'secret',
            'https://not-faked.test',
        ), 1))->verify('token', null));
    }

    public function test_orchestrator_resolves_disabled_default_named_and_unknown_providers(): void
    {
        $factory = app(CaptchaSettingsFactory::class);
        $orchestrator = new CaptchaOrchestrator($factory);
        $this->configure(['captcha.enabled' => false]);
        self::assertTrue($orchestrator->verify(null, '', null));

        $this->configure([
            'captcha.enabled' => true,
            'captcha.timeout' => 2,
            'captcha.providers' => 'turnstile',
            'captcha.definitions.turnstile' => $this->definition('TURNSTILE_SECRET_KEY', 'site-key'),
        ]);
        Http::fake(['captcha.test' => Http::response(['success' => true])]);
        self::assertTrue($orchestrator->verify(null, 'token', '127.0.0.1'));
        self::assertTrue($orchestrator->verify('turnstile', 'token', '127.0.0.1'));
        self::assertFalse($orchestrator->verify('recaptcha', 'token', '127.0.0.1'));

        $this->configure([
            'captcha.providers' => 'turnstile,recaptcha',
            'captcha.definitions.recaptcha' => $this->definition('RECAPTCHA_SECRET_KEY', 'recaptcha-site'),
        ]);
        self::assertFalse($orchestrator->verify(null, 'token', null));
    }

    /** @return array{site_key: string, secret: string, verify_url: string} */
    private function definition(string $secret, string $site): array
    {
        return ['site_key' => $site, 'secret' => $secret, 'verify_url' => 'https://captcha.test'];
    }

    private function provider(): CaptchaProviderConfig
    {
        return new CaptchaProviderConfig(
            CaptchaProviderName::Turnstile,
            'site-key',
            'secret-key',
            'https://captcha.test',
        );
    }
}
