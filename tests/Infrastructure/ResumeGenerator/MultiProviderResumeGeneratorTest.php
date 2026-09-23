<?php

declare(strict_types=1);

namespace Tests\Infrastructure\ResumeGenerator;

use App\Domain\Resume\ResumeUpstreamException;
use App\Infrastructure\ResumeGenerator\LlmProvider;
use App\Infrastructure\ResumeGenerator\MultiProviderResumeGenerator;
use App\Infrastructure\ResumeGenerator\ProviderException;
use App\Infrastructure\ResumeGenerator\RetryableProviderException;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class MultiProviderResumeGeneratorTest extends TestCase
{
    public function testFirstSuccessfulProviderShortCircuits()
    {
        $providerA = $this->prophesize(LlmProvider::class);
        $providerA->complete(Argument::any(), Argument::any())->willReturn('resume from A');

        $providerB = $this->prophesize(LlmProvider::class);
        $providerB->complete(Argument::any(), Argument::any())->shouldNotBeCalled();

        $sleeps = [];
        $sleeps = [];
        $generator = $this->createGenerator(
            ['A' => $providerA->reveal(), 'B' => $providerB->reveal()],
            $sleeps
        );

        $result = $generator->generate('job description', 'source resume');

        $this->assertSame('resume from A', $result);
        $this->assertSame([], $sleeps);
    }

    public function testRetriesThenFallsBackToNextProvider()
    {
        $providerA = $this->prophesize(LlmProvider::class);
        $providerA->complete(Argument::any(), Argument::any())
            ->willThrow(new RetryableProviderException('503'));

        $providerB = $this->prophesize(LlmProvider::class);
        $providerB->complete(Argument::any(), Argument::any())->willReturn('resume from B');

        $sleeps = [];
        $generator = $this->createGenerator(
            ['A' => $providerA->reveal(), 'B' => $providerB->reveal()],
            $sleeps,
            3
        );

        $result = $generator->generate('job description', 'source resume');

        $this->assertSame('resume from B', $result);
        $providerA->complete(Argument::cetera())->shouldHaveBeenCalledTimes(3);
        $providerB->complete(Argument::cetera())->shouldHaveBeenCalledTimes(1);
        $this->assertSame([1000000, 2000000], $sleeps);
    }

    public function testHardFailureMovesToNextProviderImmediately()
    {
        $providerA = $this->prophesize(LlmProvider::class);
        $providerA->complete(Argument::any(), Argument::any())
            ->willThrow(new ProviderException('429'));

        $providerB = $this->prophesize(LlmProvider::class);
        $providerB->complete(Argument::any(), Argument::any())->willReturn('resume from B');

        $sleeps = [];
        $generator = $this->createGenerator(
            ['A' => $providerA->reveal(), 'B' => $providerB->reveal()],
            $sleeps,
            3
        );

        $result = $generator->generate('job description', 'source resume');

        $this->assertSame('resume from B', $result);
        $providerA->complete(Argument::cetera())->shouldHaveBeenCalledTimes(1);
        $this->assertSame([], $sleeps);
    }

    public function testThrowsWhenAllProvidersExhausted()
    {
        $providerA = $this->prophesize(LlmProvider::class);
        $providerA->complete(Argument::any(), Argument::any())
            ->willThrow(new RetryableProviderException('timeout'));

        $providerB = $this->prophesize(LlmProvider::class);
        $providerB->complete(Argument::any(), Argument::any())
            ->willThrow(new RetryableProviderException('503'));

        $sleeps = [];
        $generator = $this->createGenerator(
            ['A' => $providerA->reveal(), 'B' => $providerB->reveal()],
            $sleeps,
            2
        );

        $this->expectException(ResumeUpstreamException::class);

        $generator->generate('job description', 'source resume');
    }

    /**
     * @param array<string, LlmProvider> $providers
     * @param array<int, int> $sleeps Recorded sleep delays in microseconds.
     */
    private function createGenerator(
        array $providers,
        array &$sleeps,
        int $maxAttempts = 3
    ): MultiProviderResumeGenerator {
        $logger = $this->prophesize(LoggerInterface::class);

        $sleep = function (int $microseconds) use (&$sleeps): void {
            $sleeps[] = $microseconds;
        };

        return new MultiProviderResumeGenerator(
            $providers,
            $logger->reveal(),
            $maxAttempts,
            1,
            $sleep
        );
    }
}
