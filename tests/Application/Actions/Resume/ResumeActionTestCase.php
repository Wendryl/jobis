<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Resume;

use App\Domain\Resume\ResumeRepository;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use DI\Container;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\UploadedFile;
use Slim\Psr7\Uri;
use Tests\TestCase;

abstract class ResumeActionTestCase extends TestCase
{
    protected function createJsonRequest(string $method, string $path, array $body): ServerRequestInterface
    {
        $stream = (new StreamFactory())->createStream(json_encode($body));

        return new SlimRequest(
            $method,
            new Uri('', '', 80, $path),
            new Headers(['HTTP_CONTENT_TYPE' => 'application/json']),
            [],
            [],
            $stream
        );
    }

    protected function createUploadRequest(string $method, string $path, UploadedFile $file): ServerRequestInterface
    {
        $request = $this->createRequest($method, $path);
        if (method_exists($request, 'withUploadedFiles')) {
            return $request->withUploadedFiles(['resume' => $file]);
        }

        return $request;
    }

    protected function stubServices(
        Container $container,
        ResumeRepository $resumeRepository,
        ?ResumeGenerator $generator = null,
        ?ResumePdfGenerator $pdfGenerator = null,
        ?ResumeRateLimiter $rateLimiter = null
    ): void {
        $container->set(ResumeRepository::class, $resumeRepository);
        if ($generator !== null) {
            $container->set(ResumeGenerator::class, $generator);
        }
        if ($pdfGenerator !== null) {
            $container->set(ResumePdfGenerator::class, $pdfGenerator);
        }
        if ($rateLimiter !== null) {
            $container->set(ResumeRateLimiter::class, $rateLimiter);
        }
    }
}
