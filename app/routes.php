<?php

declare(strict_types=1);

use App\Application\Actions\OpenApi\OpenApiJsonAction;
use App\Application\Actions\OpenApi\SwaggerUiAction;
use App\Application\Actions\Resume\GenerateResumeAction;
use App\Application\Actions\Resume\UploadResumeAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/openapi.json', OpenApiJsonAction::class);
    $app->get('/docs', SwaggerUiAction::class);

    $app->post('/resume', UploadResumeAction::class);
    $app->post('/resume/generate', GenerateResumeAction::class);
};
