<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Resume;

use App\Domain\Resume\ResumeRepository;
use DI\Container;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\UploadedFile;

class UploadResumeActionTest extends ResumeActionTestCase
{
    public function testUploadStoresResume()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeRepositoryProphecy = $this->prophesize(ResumeRepository::class);
        $resumeRepositoryProphecy
            ->saveResumeContent('# John Doe')
            ->shouldBeCalledOnce();

        $this->stubServices($container, $resumeRepositoryProphecy->reveal());

        $file = new UploadedFile(
            (new StreamFactory())->createStream('# John Doe'),
            'RESUME.md',
            'text/markdown'
        );

        $request = $this->createUploadRequest('POST', '/resume', $file);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());

        $resumeRepositoryProphecy->checkProphecyMethodsPredictions();
    }

    public function testUploadWithoutFileReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeRepositoryProphecy = $this->prophesize(ResumeRepository::class);
        $resumeRepositoryProphecy
            ->saveResumeContent(\Prophecy\Argument::any())
            ->shouldNotBeCalled();

        $this->stubServices($container, $resumeRepositoryProphecy->reveal());

        $request = $this->createRequest('POST', '/resume');
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
