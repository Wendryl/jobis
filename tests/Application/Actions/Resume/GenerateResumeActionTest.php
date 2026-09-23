<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Resume;

use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumeUpstreamException;
use DI\Container;

class GenerateResumeActionTest extends ResumeActionTestCase
{
    private const JOB_DESCRIPTION = 'We need an advanced Laravel developer with DDD experience.';

    private const RESUME_CONTENT = 'John Doe, Senior PHP Developer with Laravel and DDD.';

    public function testGenerateReturnsPdf()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(self::JOB_DESCRIPTION, self::RESUME_CONTENT)->willReturn('# Tailored');

        $pdfProphecy = $this->prophesize(ResumePdfGenerator::class);
        $pdfProphecy->generate('# Tailored')->willReturn('%PDF-1.4 fake');

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(true);

        $this->stubServices(
            $container,
            null,
            $generatorProphecy->reveal(),
            $pdfProphecy->reveal(),
            $limiterProphecy->reveal()
        );

        $request = $this->createJsonRequest('POST', '/resume/generate', [
            'job_description' => self::JOB_DESCRIPTION,
            'resume_content' => self::RESUME_CONTENT,
        ]);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertSame('%PDF-1.4 fake', (string) $response->getBody());
    }

    public function testGenerateWithShortDescriptionReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(\Prophecy\Argument::any(), \Prophecy\Argument::any())->shouldNotBeCalled();

        $this->stubServices($container, null, $generatorProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', [
            'job_description' => 'short',
            'resume_content' => self::RESUME_CONTENT,
        ]);
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGenerateWithMissingResumeContentReturnsBadRequest()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(\Prophecy\Argument::any(), \Prophecy\Argument::any())->shouldNotBeCalled();

        $this->stubServices($container, null, $generatorProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', [
            'job_description' => self::JOB_DESCRIPTION,
        ]);
        $response = $app->handle($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGenerateWhenRateLimitedReturnsTooManyRequests()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(false);

        $this->stubServices($container, null, null, null, $limiterProphecy->reveal());

        $request = $this->createJsonRequest('POST', '/resume/generate', [
            'job_description' => self::JOB_DESCRIPTION,
            'resume_content' => self::RESUME_CONTENT,
        ]);
        $response = $app->handle($request);

        $this->assertSame(429, $response->getStatusCode());
    }

    public function testGenerateWhenUpstreamFailsReturnsBadGateway()
    {
        $app = $this->getAppInstance();
        /** @var Container $container */
        $container = $app->getContainer();

        $generatorProphecy = $this->prophesize(ResumeGenerator::class);
        $generatorProphecy->generate(\Prophecy\Argument::any(), \Prophecy\Argument::any())
            ->willThrow(new ResumeUpstreamException('Gemini timeout'));

        $limiterProphecy = $this->prophesize(ResumeRateLimiter::class);
        $limiterProphecy->isAllowed()->willReturn(true);

        $this->stubServices(
            $container,
            null,
            $generatorProphecy->reveal(),
            null,
            $limiterProphecy->reveal()
        );

        $request = $this->createJsonRequest('POST', '/resume/generate', [
            'job_description' => self::JOB_DESCRIPTION,
            'resume_content' => self::RESUME_CONTENT,
        ]);
        $response = $app->handle($request);

        $this->assertSame(502, $response->getStatusCode());
    }
}
