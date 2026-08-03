<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Services\Dns\DohMxResolver;
use App\Services\Dns\EmailDomainValidator;
use App\Services\Dns\NativeMxResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

final class ApiFeatureTest extends TestCase
{
    /** @var array<string, string> */
    private array $contact = [
        'nome' => 'Pessoa da Silva',
        'email' => 'pessoa@example.com',
        'telefone' => '(48) 99999-9999',
        'assunto' => 'Orientação jurídica',
        'mensagem' => 'Gostaria de entender os próximos passos do meu caso.',
    ];

    public function test_site_configuration_and_api_not_found_contracts(): void
    {
        $response = $this->getJson('/api/site-config')->assertOk();
        $response->assertJsonPath('whatsLinkNumber', '5548988026847')
            ->assertJsonPath('fieldLimits.mensagem.min', 10)
            ->assertJsonPath('captchaProviders', []);

        $this->getJson('/api/missing')->assertNotFound();
        $this->getJson('/api/contact')->assertNotFound();
    }

    public function test_rejects_invalid_honeypot_and_oversized_contacts(): void
    {
        Mail::fake();
        $invalid = $this->postJson('/api/contact', ['nome' => 'A'])->assertStatus(400);
        self::assertCount(5, $invalid->json('errors'));
        $this->postJson('/api/contact', [...$this->contact, 'website' => 'spam'])->assertStatus(400);
        $this->postJson('/api/contact', [...$this->contact, 'assunto' => "Assunto\r\nBcc: attacker@example.com"])
            ->assertStatus(400)
            ->assertJsonFragment(['message' => trans('site.invalid_subject_chars')]);
        $this->postJson('/api/contact', [...$this->contact, 'mensagem' => str_repeat('X', 65_537)])
            ->assertStatus(413)
            ->assertJsonFragment(['message' => trans('site.payload_too_large')]);
        Mail::assertNothingSent();
    }

    public function test_sends_valid_json_and_form_contacts(): void
    {
        Mail::fake();
        $this->postJson('/api/contact', $this->contact)
            ->assertOk()
            ->assertJson(['message' => trans('site.contact_success'), 'conversion' => true]);
        $this->post('/api/contact', $this->contact)->assertOk();
        $this->postJson('/api/contact', array_map(
            static fn (string $value): string => "  {$value}  ",
            $this->contact,
        ))->assertOk();
        Mail::assertSentCount(3);
        Mail::assertSent(ContactMessage::class, function (ContactMessage $message): bool {
            return $message->contact->subject === $this->contact['assunto'];
        });
    }

    public function test_rejects_email_domain_without_mx(): void
    {
        $this->configure(['site.email_dns_validation' => true]);
        $native = new NativeMxResolver(static fn (): array => []);
        Http::fake(['cloudflare-dns.com/*' => Http::response(['Answer' => []])]);
        $this->app->instance(EmailDomainValidator::class, new EmailDomainValidator($native, new DohMxResolver));
        Mail::fake();

        $this->postJson('/api/contact', [...$this->contact, 'email' => 'pessoa@inventado.test'])
            ->assertStatus(400)
            ->assertJsonFragment(['message' => trans('site.email_domain_unavailable')]);
        Mail::assertNothingSent();
    }

    public function test_captcha_uses_cloudflare_forwarded_and_remote_ips(): void
    {
        $this->configure([
            'captcha.enabled' => true,
            'captcha.providers' => 'turnstile',
            'captcha.timeout' => 2,
            'captcha.definitions.turnstile' => [
                'site_key' => 'site',
                'secret' => 'TURNSTILE_SECRET_KEY',
                'verify_url' => 'https://captcha.test',
            ],
        ]);
        Mail::fake();
        $cases = [
            [['CF-Connecting-IP' => ' 203.0.113.1 '], '203.0.113.1'],
            [['X-Forwarded-For' => '203.0.113.2, 10.0.0.1'], '203.0.113.2'],
            [['CF-Connecting-IP' => ' ', 'X-Forwarded-For' => '203.0.113.3'], '203.0.113.3'],
            [['CF-Connecting-IP' => ' ', 'X-Forwarded-For' => ' '], '127.0.0.1'],
            [[], '127.0.0.1'],
        ];
        Http::fake(['captcha.test' => Http::sequence()
            ->push(['success' => true])
            ->push(['success' => true])
            ->push(['success' => true])
            ->push(['success' => true])
            ->push(['success' => true])
            ->push(['success' => false])]);

        foreach ($cases as [$headers, $expectedIp]) {
            $this->flushHeaders();
            $this->withHeaders($headers)->postJson('/api/contact', [
                ...$this->contact,
                'captchaProvider' => 'turnstile',
                'captchaToken' => 'token',
            ])->assertOk();
            $recorded = Http::recorded();
            self::assertNotEmpty($recorded);
            $last = $recorded->last();
            self::assertIsArray($last);
            self::assertSame($expectedIp, $last[0]->data()['remoteip']);
        }
        Mail::assertSentCount(5);

        $this->flushHeaders();
        $this->postJson('/api/contact', [
            ...$this->contact,
            'captchaProvider' => 'turnstile',
            'captchaToken' => 'invalid',
        ])->assertStatus(400)->assertJsonFragment(['message' => trans('site.captcha_failed')]);
    }

    public function test_mail_transport_and_unexpected_failures_return_safe_errors(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new TransportException('smtp'));
        $this->postJson('/api/contact', $this->contact)->assertStatus(502)
            ->assertJsonFragment(['message' => trans('site.contact_failed')]);

        Mail::clearResolvedInstance('mailer');
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('unexpected'));
        $this->postJson('/api/contact', $this->contact)->assertStatus(500)
            ->assertJsonFragment(['message' => trans('site.contact_failed')]);
    }
}
