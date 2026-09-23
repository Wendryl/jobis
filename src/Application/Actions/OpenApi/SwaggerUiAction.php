<?php

declare(strict_types=1);

namespace App\Application\Actions\OpenApi;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class SwaggerUiAction extends Action
{
    private string $openApiUrl;

    public function __construct(LoggerInterface $logger, string $openApiUrl)
    {
        parent::__construct($logger);
        $this->openApiUrl = $openApiUrl;
    }

    protected function action(): Response
    {
        $specUrl = htmlspecialchars($this->openApiUrl, ENT_QUOTES);
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobis API — Documentation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: "{$specUrl}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset,
            ],
            layout: "BaseLayout",
        });
    </script>
</body>
</html>
HTML;

        $this->response->getBody()->write($html);

        return $this->response
                    ->withHeader('Content-Type', 'text/html; charset=utf-8')
                    ->withHeader('Cache-Control', 'no-store');
    }
}
