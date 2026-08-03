<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class DocsController extends Controller
{
    public function specification(): JsonResponse
    {
        $this->authorizeDocs();

        return response()->json([
            'openapi' => '3.1.0',
            'info' => ['title' => 'Dias Kovaltchuk Advogadas Associadas API', 'version' => '1.0.0'],
            'paths' => [
                '/api/site-config' => [
                    'get' => ['summary' => 'Retorna a configuração pública do site', 'responses' => ['200' => ['description' => 'Configuração pública']]],
                ],
                '/api/contact' => [
                    'post' => [
                        'summary' => 'Envia uma solicitação de contato',
                        'responses' => [
                            '200' => ['description' => 'Contato enviado'],
                            '400' => ['description' => 'Dados inválidos'],
                            '502' => ['description' => 'Falha no envio'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function ui(): Response
    {
        $this->authorizeDocs();

        return response(<<<'HTML'
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dias Kovaltchuk API</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css"></head><body><div id="swagger-ui"></div><script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script><script>SwaggerUIBundle({url:"/openapi.json",dom_id:"#swagger-ui"});</script></body></html>
HTML, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function authorizeDocs(): void
    {
        abort_unless((bool) config('site.swagger_enabled', false), 404);
    }
}
