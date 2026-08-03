<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\Captcha\CaptchaOrchestrator;
use App\Services\ContactMailer;
use App\Services\Dns\EmailDomainValidator;
use App\ValueObjects\ContactData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

final class ContactController extends Controller
{
    public function __invoke(
        ContactRequest $request,
        EmailDomainValidator $emailValidator,
        CaptchaOrchestrator $captcha,
        ContactMailer $mailer,
    ): JsonResponse {
        if ($request->filled('website')) {
            Log::warning('Honeypot acionado no formulário de contato.');

            return response()->json(['message' => trans('site.honeypot')], 400);
        }

        $request->validated();
        $contact = new ContactData(
            $request->string('nome')->toString(),
            $request->string('email')->toString(),
            $request->string('telefone')->toString(),
            $request->string('assunto')->toString(),
            $request->string('mensagem')->toString(),
        );
        if ((bool) config('site.email_dns_validation', true) && ! $emailValidator->accepts($contact->email)) {
            $message = trans('site.email_domain_unavailable');

            return response()->json(['message' => $message, 'errors' => [$message]], 400);
        }

        if (! $captcha->verify(
            $request->string('captchaProvider')->toString() ?: null,
            $request->string('captchaToken')->toString(),
            $this->clientIp($request),
        )) {
            return response()->json(['message' => trans('site.captcha_failed')], 400);
        }

        try {
            $mailer->send($contact);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Erro SMTP ao enviar contato.', ['exception' => $exception::class]);

            return response()->json(['message' => trans('site.contact_failed')], 502);
        } catch (Throwable $exception) {
            Log::error('Erro inesperado ao enviar contato.', ['exception' => $exception::class]);

            return response()->json(['message' => trans('site.contact_failed')], 500);
        }

        return response()->json(['message' => trans('site.contact_success'), 'conversion' => true]);
    }

    private function clientIp(ContactRequest $request): ?string
    {
        $cloudflare = $this->normalizedHeader($request, 'CF-Connecting-IP');
        if ($cloudflare !== null) {
            return $cloudflare;
        }

        $forwarded = $this->normalizedHeader($request, 'X-Forwarded-For');
        if ($forwarded !== null) {
            $first = explode(',', $forwarded, 2)[0];

            return trim($first);
        }

        return $request->ip();
    }

    private function normalizedHeader(ContactRequest $request, string $name): ?string
    {
        $header = $request->header($name);
        if (! is_string($header)) {
            return null;
        }

        $header = trim($header);

        return $header === '' ? null : $header;
    }
}
