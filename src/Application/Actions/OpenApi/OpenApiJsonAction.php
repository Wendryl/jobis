<?php

declare(strict_types=1);

namespace App\Application\Actions\OpenApi;

use App\Application\Actions\Action;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class OpenApiJsonAction extends Action
{
    private Generator $generator;

    private string $srcDir;

    public function __construct(LoggerInterface $logger, Generator $generator, string $srcDir)
    {
        parent::__construct($logger);
        $this->generator = $generator;
        $this->srcDir = $srcDir;
    }

    protected function action(): Response
    {
        $openapi = $this->generator->generate([$this->srcDir]);
        $json = $openapi->toJson();

        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Cache-Control', 'no-store');
    }
}
