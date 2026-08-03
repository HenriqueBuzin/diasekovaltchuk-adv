<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Dns\DohMxResolver;
use App\Services\Dns\EmailDomainValidator;
use App\Services\Dns\NativeMxResolver;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

final class DnsTest extends TestCase
{
    public function test_native_resolver_handles_records_and_lookup_failure(): void
    {
        $resolver = new NativeMxResolver(static fn (string $domain, int $type): array => [
            ['target' => "mail.{$domain}", 'type' => $type],
            ['type' => 'MX'],
            ['target' => 123],
            'invalid',
        ]);
        self::assertSame(['mail.example.com'], $resolver->resolve('example.com'));

        $failed = new NativeMxResolver(static fn (): false => false);
        self::assertSame([], $failed->resolve('example.com'));

        self::assertInstanceOf(NativeMxResolver::class, app(NativeMxResolver::class));
    }

    public function test_doh_resolver_handles_success_invalid_data_http_failure_and_exception(): void
    {
        $resolver = new DohMxResolver;
        Http::fake(['cloudflare-dns.com/*' => Http::sequence()
            ->push(['Answer' => [
                ['data' => '"10 mail.example.com."'],
                ['data' => '20 backup.example.com.'],
                ['other' => 'ignored'],
                ['data' => 123],
                'invalid',
            ]])
            ->push([], 500)
            ->push(['Answer' => 'invalid'])]);
        self::assertSame(['10 mail.example.com.', '20 backup.example.com.'], $resolver->resolve('example.com'));

        self::assertSame([], $resolver->resolve('example.com'));

        self::assertSame([], $resolver->resolve('example.com'));
    }

    public function test_doh_resolver_handles_transport_exceptions(): void
    {
        Http::fake(static function (): never {
            throw new \RuntimeException('network');
        });
        $resolver = new DohMxResolver;
        self::assertSame([], $resolver->resolve('not-faked.test'));
    }

    public function test_email_domain_validator_uses_native_fallback_and_rejects_null_mx(): void
    {
        $native = new NativeMxResolver(static fn (): array => [['target' => 'mail.example.com.']]);
        $validator = new EmailDomainValidator($native, new DohMxResolver);
        self::assertSame('example.com', $validator->domain(' Person@Example.COM '));
        self::assertSame('example.com', $validator->domain('alias@department@example.com'));
        self::assertTrue($validator->accepts('person@example.com'));

        $emptyNative = new NativeMxResolver(static fn (): array => []);
        Http::fake(['cloudflare-dns.com/*' => Http::sequence()
            ->push(['Answer' => [['data' => '10 fallback.example.com.']]])
            ->push(['Answer' => [['data' => '0 .']]])]);
        self::assertTrue((new EmailDomainValidator($emptyNative, new DohMxResolver))->accepts('person@example.com'));

        self::assertFalse((new EmailDomainValidator($emptyNative, new DohMxResolver))->accepts('person@example.com'));

        $priorityNative = new NativeMxResolver(static fn (): array => [['target' => '10 mail.example.com.']]);
        self::assertTrue((new EmailDomainValidator($priorityNative, new DohMxResolver))->accepts('person@example.com'));

        $plainNative = new NativeMxResolver(static fn (): array => [['target' => ' mail.example.com ']]);
        self::assertTrue((new EmailDomainValidator($plainNative, new DohMxResolver))->accepts('person@example.com'));

        $nonPriorityNative = new NativeMxResolver(static fn (): array => [['target' => 'mail example.com']]);
        self::assertTrue((new EmailDomainValidator($nonPriorityNative, new DohMxResolver))->accepts('person@example.com'));

        foreach (['invalid', '@example.com', 'person@'] as $invalid) {
            try {
                $validator->domain($invalid);
                self::fail('O e-mail inválido deveria falhar.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('e-mail', $exception->getMessage());
            }
        }
    }
}
