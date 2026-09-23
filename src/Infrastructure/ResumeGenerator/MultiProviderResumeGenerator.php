<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumeUpstreamException;
use Psr\Log\LoggerInterface;

class MultiProviderResumeGenerator implements ResumeGenerator
{
    /** @var array<string, LlmProvider> */
    private array $providers;

    private LoggerInterface $logger;

    private int $maxAttempts;

    private int $retryDelaySeconds;

    /** @var callable(int): void */
    private $sleep;

    /**
     * @param array<string, LlmProvider> $providers Providers tried in order, first success wins.
     * @param callable(int): void|null $sleep Injectable sleep function, primarily for tests.
     */
    public function __construct(
        array $providers,
        LoggerInterface $logger,
        int $maxAttempts = 3,
        int $retryDelaySeconds = 1,
        ?callable $sleep = null
    ) {
        $this->providers = $providers;
        $this->logger = $logger;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->retryDelaySeconds = max(0, $retryDelaySeconds);
        $this->sleep = $sleep ?? static fn (int $microseconds) => usleep($microseconds);
    }

    public function generate(string $jobDescription, string $resumeContent): string
    {
        $systemInstruction = $this->systemInstruction();
        $userMessage = $this->buildUserMessage($jobDescription, $resumeContent);

        foreach ($this->providers as $providerName => $provider) {
            for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
                try {
                    $text = $provider->complete($systemInstruction, $userMessage);
                    $this->logger->info('Resume generated via LLM provider {provider}.', [
                        'provider' => $providerName,
                    ]);
                    return $text;
                } catch (RetryableProviderException $exception) {
                    $this->logger->warning(
                        'LLM provider {provider} failed (attempt {attempt}/{maxAttempts}): {error}',
                        [
                            'provider' => $providerName,
                            'attempt' => $attempt,
                            'maxAttempts' => $this->maxAttempts,
                            'error' => $exception->getMessage(),
                        ]
                    );

                    if ($attempt < $this->maxAttempts) {
                        ($this->sleep)($this->backoffMicroseconds($attempt));
                    }
                } catch (ProviderException $exception) {
                    $this->logger->warning('LLM provider {provider} rejected the request: {error}', [
                        'provider' => $providerName,
                        'error' => $exception->getMessage(),
                    ]);
                    break;
                }
            }
        }

        throw new ResumeUpstreamException('All LLM providers failed to generate a resume.');
    }

    private function backoffMicroseconds(int $attempt): int
    {
        return $this->retryDelaySeconds * 1_000_000 * (2 ** ($attempt - 1));
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You are an expert resume writer. Given a job description and a source resume written in
Markdown, produce a single tailored resume in Markdown that maximizes the candidate's
chances of getting an interview for that specific job.

Rules:
- Keep factual claims grounded in the source resume. Do not invent experience.
- Reorder and emphasize the most relevant skills, experience and achievements for the job.
- Mirror the technical keywords and requirements listed in the job description.
- Match the language of the job description (if the job description is Portuguese, write in Portuguese).
- Structure the output with clear Markdown headings and bullet points.
- Output ONLY the resume Markdown, with no preamble or commentary.
- Try to keep it concise and short, preferably a single page resume.
- The professional summary section of the resume should not be too long, try to keep it to a maximum of 360 characters.
PROMPT;
    }

    private function buildUserMessage(string $jobDescription, string $resumeContent): string
    {
        return "JOB DESCRIPTION\n===============\n{$jobDescription}\n\n"
            . "SOURCE RESUME\n=============\n{$resumeContent}";
    }
}
