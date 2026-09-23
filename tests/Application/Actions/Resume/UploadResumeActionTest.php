<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Resume;

use App\Domain\Resume\ResumeExtractor;
use DI\Container;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

class UploadResumeActionTest extends ResumeActionTestCase
{
    public function testUploadExtractsAndReturnsText()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $extractorProphecy = $this->prophesize(ResumeExtractor::class);
        $extractorProphecy
            ->extractFromPdf('%PDF-1.4 fake resume')
            ->shouldBeCalledOnce()
            ->willReturn("John Doe\nSenior PHP Developer\nSkills: PHP, Slim, Laravel");

        $this->stubServices($container, $extractorProphecy->reveal());

        $file = new UploadedFile(
            (new StreamFactory())->createStream('%PDF-1.4 fake resume'),
            'resume.pdf',
            'application/pdf'
        );

        $request = $this->createUploadRequest('POST', '/resume', $file);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(
            "John Doe\nSenior PHP Developer\nSkills: PHP, Slim, Laravel",
            $body['data']['text']
        );

        $extractorProphecy->checkProphecyMethodsPredictions();
    }

    public function testUploadWithoutFileReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $extractorProphecy = $this->prophesize(ResumeExtractor::class);
        $extractorProphecy->extractFromPdf(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->stubServices($container, $extractorProphecy->reveal());

        $request = $this->createRequest('POST', '/resume');
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUploadNonPdfReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $extractorProphecy = $this->prophesize(ResumeExtractor::class);
        $extractorProphecy->extractFromPdf(\Prophecy\Argument::any())->shouldNotBeCalled();

        $this->stubServices($container, $extractorProphecy->reveal());

        $file = new UploadedFile(
            (new StreamFactory())->createStream('plain text resume'),
            'resume.txt',
            'text/plain'
        );

        $request = $this->createUploadRequest('POST', '/resume', $file);
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
