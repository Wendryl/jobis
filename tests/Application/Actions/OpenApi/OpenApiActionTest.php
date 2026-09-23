<?php

declare(strict_types=1);

namespace Tests\Application\Actions\OpenApi;

use Tests\TestCase;

class OpenApiActionTest extends TestCase
{
    public function testOpenApiJsonReturnsValidSpec()
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest('GET', '/openapi.json');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('3.0.0', $body['openapi']);
        $this->assertSame('Jobis API', $body['info']['title']);
        $this->assertArrayHasKey('/resume', $body['paths']);
        $this->assertArrayHasKey('/resume/generate', $body['paths']);
    }

    public function testSwaggerUiReturnsHtml()
    {
        $app = $this->getAppInstance();

        $request = $this->createRequest('GET', '/docs');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $html = (string) $response->getBody();
        $this->assertStringContainsString('swagger-ui', $html);
        $this->assertStringContainsString('/openapi.json', $html);
    }
}
