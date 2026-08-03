<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\RejectLargeRequests;
use App\Mail\ContactMessage;
use App\Rules\ValidPhone;
use App\Services\ContactMailer;
use App\Services\SiteConfiguration;
use App\ValueObjects\ContactData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class ApplicationServicesTest extends TestCase
{
    public function test_phone_rule_accepts_ten_or_eleven_digits_and_rejects_other_values(): void
    {
        $rule = new ValidPhone;
        $errors = [];
        $fail = static function (string $message) use (&$errors): void {
            $errors[] = $message;
        };

        $rule->validate('telefone', '(48) 9999-9999', $fail);
        $rule->validate('telefone', '(48) 99999-9999', $fail);
        $rule->validate('telefone', 'abcdefghij', $fail);
        $rule->validate('telefone', '123', $fail);
        $rule->validate('telefone', [], $fail);
        self::assertCount(3, $errors);
    }

    public function test_contact_mailable_and_mailer_preserve_contact_data(): void
    {
        Mail::fake();
        $contact = $this->contact();
        $mailable = new ContactMessage($contact);
        self::assertSame('Orientação jurídica', $mailable->envelope()->subject);
        self::assertSame('person@example.com', $mailable->envelope()->replyTo[0]->address);
        self::assertSame('emails.contact', $mailable->content()->text);

        app(ContactMailer::class)->send($contact);
        Mail::assertSent(ContactMessage::class, fn (ContactMessage $message): bool => $message->contact === $contact);
    }

    public function test_site_configuration_exposes_only_public_values_and_validates_recipients(): void
    {
        $this->configure(['captcha.enabled' => false]);
        $configuration = app(SiteConfiguration::class);
        $data = $configuration->publicData();
        self::assertSame('5548988026847', $data['whatsLinkNumber']);
        self::assertSame([], $data['captchaProviders']);
        self::assertSame('', $data['turnstileSiteKey']);
        self::assertSame(['destino@example.com'], $configuration->recipients());

        $this->configure(['site.contact_to' => ' one@example.com, , two@example.com ']);
        self::assertSame(['one@example.com', 'two@example.com'], $configuration->recipients());

        $this->configure(['site.contact_to' => ' , ']);
        $this->expectException(\RuntimeException::class);
        $configuration->recipients();
    }

    public function test_site_configuration_rejects_missing_required_public_values(): void
    {
        $this->configure(['captcha.enabled' => false, 'site.contact_email' => '']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CONTACT_EMAIL');
        app(SiteConfiguration::class)->publicData();
    }

    public function test_site_configuration_rejects_non_string_required_values_and_maps_captcha(): void
    {
        $this->configure([
            'captcha.enabled' => true,
            'captcha.providers' => 'turnstile',
            'captcha.definitions.turnstile' => [
                'site_key' => 'public-key',
                'secret' => 'TURNSTILE_SECRET_KEY',
                'verify_url' => 'https://captcha.test',
            ],
        ]);
        self::assertSame('turnstile', app(SiteConfiguration::class)->publicData()['captchaProviders'][0]['name']);

        $this->configure(['captcha.enabled' => false, 'site.contact_email' => '  contato@example.com  ']);
        self::assertSame('contato@example.com', app(SiteConfiguration::class)->publicData()['contactEmail']);

        $this->configure(['captcha.enabled' => false, 'site.contact_email' => []]);
        $this->expectException(\RuntimeException::class);
        app(SiteConfiguration::class)->publicData();
    }

    public function test_request_size_middleware_accepts_rejects_and_validates_next_response(): void
    {
        $middleware = new RejectLargeRequests;
        $small = Request::create('/api/contact', 'POST', server: ['CONTENT_LENGTH' => 100]);
        $response = $middleware->handle($small, static fn (): Response => new Response('ok'));
        self::assertSame('ok', $response->getContent());

        $bodySized = Request::create('/api/contact', 'POST', content: 'body');
        self::assertSame('ok', $middleware->handle($bodySized, static fn (): Response => new Response('ok'))->getContent());

        $equalSized = Request::create('/api/contact', 'POST', server: ['CONTENT_LENGTH' => 4], content: 'body');
        self::assertSame('ok', $middleware->handle($equalSized, static fn (): Response => new Response('ok'))->getContent());

        $large = Request::create('/api/contact', 'POST', server: ['CONTENT_LENGTH' => 65_537]);
        self::assertSame(413, $middleware->handle($large, static fn (): Response => new Response)->getStatusCode());

        $this->expectException(LogicException::class);
        $middleware->handle($small, static fn (): string => 'invalid');
    }

    private function contact(): ContactData
    {
        return new ContactData(
            'Pessoa da Silva',
            'person@example.com',
            '(48) 99999-9999',
            'Orientação jurídica',
            'Gostaria de entender os próximos passos.',
        );
    }
}
