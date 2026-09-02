<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Resume;

use App\Domain\Resume\ResumeNotFoundException;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Domain\Resume\ResumeRepository;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumeUpstreamException;
use DI\Container;

class GenerateResumeActionTest extends ResumeActionTestCase
{
    private const JOB_DESCRIPTION = 'We need an advanced Laravel developer with DDD experience.';

    public function testGenerateReturnsPdf()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->willReturn('# Source');
        $resumeProphecy->getCachedPdfPath(\Prophecy\Argument::any())->willReturn(null);
        $resumeProphecy->saveCachedPdf(\Prophecy\Argument::any(), '%PDF-1.4 fake')->willReturn('/tmp/x');

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(self::JOB_DESCRIPTION, '# Source')->willReturn('# Tailored');

        $pdfProphecy = $this->prophesize(ResumePdfGenerator::class);
        $pdfProphecy->generate('# Tailored')->willReturn('%PDF-1.4 fake');

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(true);

        $this->stubServices(
            $container,
            $resumeProphecy->reveal(),
            $generatorProphecy->reveal(),
            $pdfProphecy->reveal(),
            $limiterProphecy->reveal()
        );

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => self::JOB_DESCRIPTION]);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertSame('%PDF-1.4 fake', (string) $response->getBody());
    }

    public function testGenerateServesCachedPdf()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->willReturn('# Source');
        $resumeProphecy->getCachedPdfPath(\Prophecy\Argument::any())->willReturn(__DIR__ . '/fixtures/fake.pdf');

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(\Prophecy\Argument::any(), \Prophecy\Argument::any())->shouldNotBeCalled();

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->shouldNotBeCalled();

        $this->stubServices(
            $container,
            $resumeProphecy->reveal(),
            $generatorProphecy->reveal(),
            null,
            $limiterProphecy->reveal()
        );

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => self::JOB_DESCRIPTION]);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGenerateWithoutResumeReturnsNotFound()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->willThrow(new ResumeNotFoundException());

        $this->stubServices($container, $resumeProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => self::JOB_DESCRIPTION]);
        $response = $app->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGenerateWithShortDescriptionReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->shouldNotBeCalled();

        $this->stubServices($container, $resumeProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => 'short']);
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGenerateWhenRateLimitedReturnsTooManyRequests()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->willReturn('# Source');
        $resumeProphecy->getCachedPdfPath(\Prophecy\Argument::any())->willReturn(null);

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(false);

        $this->stubServices($container, $resumeProphecy->reveal(), null, null, $limiterProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => self::JOB_DESCRIPTION]);
        $response = $app->handle($request);

        $this->assertSame(429, $response->getStatusCode());
    }

    public function testGenerateWhenUpstreamFailsReturnsBadGateway()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $resumeProphecy = $this->prophesize(ResumeRepository::class);
        $resumeProphecy->getResumeContent()->willReturn('# Source');
        $resumeProphecy->getCachedPdfPath(\Prophecy\Argument::any())->willReturn(null);

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(\Prophecy\Argument::any(), \Prophecy\Argument::any())
            ->willThrow(new ResumeUpstreamException('Gemini timeout'));

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(true);

        $this->stubServices(
            $container,
            $resumeProphecy->reveal(),
            $generatorProphecy->reveal(),
            null,
            $limiterProphecy->reveal()
        );

        $request = $this->createJsonRequest('POST', '/resume/generate', ['job_description' => self::JOB_DESCRIPTION]);
        $response = $app->handle($request);

        $this->assertSame(502, $response->getStatusCode());
    }
}
