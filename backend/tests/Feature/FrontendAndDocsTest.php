<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class FrontendAndDocsTest extends TestCase
{
    public function test_spa_fallback_serves_build_and_reports_missing_build(): void
    {
        $index = public_path('index.html');
        @unlink($index);
        $this->get('/')->assertStatus(503)->assertJsonFragment(['message' => trans('site.frontend_missing')]);

        file_put_contents($index, '<title>React frontend</title>');
        try {
            $this->get('/')->assertOk();
            $this->get('/rota-do-react')->assertOk();
            self::assertStringContainsString('React frontend', (string) file_get_contents($index));
        } finally {
            unlink($index);
        }
    }

    public function test_swagger_is_available_only_when_enabled(): void
    {
        $this->get('/docs')->assertNotFound();
        $this->get('/openapi.json')->assertNotFound();

        $this->configure(['site.swagger_enabled' => true]);
        $this->get('/docs')->assertOk()->assertSee('SwaggerUIBundle', false);
        $specification = $this->getJson('/openapi.json')->assertOk();
        $specification->assertJsonPath('openapi', '3.1.0');
        self::assertArrayHasKey('/api/contact', $specification->json('paths'));
    }
}
