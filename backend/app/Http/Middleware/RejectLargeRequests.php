<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class RejectLargeRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $declaredLength = (int) $request->server('CONTENT_LENGTH', 0);
        $actualLength = strlen($request->getContent());
        $contentLength = $declaredLength > $actualLength ? $declaredLength : $actualLength;
        if ($contentLength > 65_536) {
            return new JsonResponse(['message' => trans('site.payload_too_large')], 413);
        }

        $response = $next($request);
        if (! $response instanceof Response) {
            throw new LogicException('O próximo middleware deve retornar uma resposta HTTP.');
        }

        return $response;
    }
}
