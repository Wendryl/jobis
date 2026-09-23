<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeExtractor;

use App\Domain\Resume\ResumeExtractor;
use App\Domain\Resume\ResumeUpstreamException;
use App\Infrastructure\ResumeGenerator\LlmProvider;
use App\Infrastructure\ResumeGenerator\ProviderException;
use App\Infrastructure\ResumeGenerator\RetryableProviderException;
use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser;

class MultiProviderResumeExtractor implements ResumeExtractor
{
    /** @var array<string, LlmProvider> */
    private array $providers;

    private LoggerInterface $logger;

    private int $maxAttempts;

    private int $retryDelaySeconds;

    /** @var callable(int): void */
    private $sleep;

    private Parser $pdfParser;

    /**
     * @param array<string, LlmProvider> $providers Providers tried in order, first success wins.
     * @param callable(int): void|null $sleep Injectable sleep function, primarily for tests.
     */
    public function __construct(
        array $providers,
        LoggerInterface $logger,
        Parser $pdfParser,
        int $maxAttempts = 3,
        int $retryDelaySeconds = 1,
        ?callable $sleep = null
    ) {
        $this->providers = $providers;
        $this->logger = $logger;
        $this->pdfParser = $pdfParser;
        $this->maxAttempts = max(1, $maxAttempts);
        $this->retryDelaySeconds = max(0, $retryDelaySeconds);
        $this->sleep = $sleep ?? static fn (int $microseconds) => usleep($microseconds);
    }

    public function extractFromPdf(string $pdfContent): string
    {
        $rawText = $this->pdfParser->parseContent($pdfContent)->getText();
        if (trim($rawText) === '') {
            throw new ResumeUpstreamException('Unable to extract any text from the supplied PDF.');
        }

        $systemInstruction = $this->systemInstruction();
        $userMessage = "SOURCE RESUME\n=============\n{$rawText}";

        foreach ($this->providers as $providerName => $provider) {
            for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
                try {
                    $text = $provider->complete($systemInstruction, $userMessage);
                    $this->logger->info('Resume extracted via LLM provider {provider}.', [
                        'provider' => $providerName,
                    ]);
                    return $text;
                } catch (RetryableProviderException $exception) {
                    $this->logger->warning(
                        'LLM provider {provider} failed during extraction (attempt {attempt}/{maxAttempts}): {error}',
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
                    $this->logger->warning('LLM provider {provider} rejected the extraction request: {error}', [
                        'provider' => $providerName,
                        'error' => $exception->getMessage(),
                    ]);
                    break;
                }
            }
        }

        throw new ResumeUpstreamException('All LLM providers failed to extract the resume.');
    }

    private function backoffMicroseconds(int $attempt): int
    {
        return $this->retryDelaySeconds * 1_000_000 * (2 ** ($attempt - 1));
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You are an expert resume parser. Given the raw text extracted from a resume PDF, produce a
concise plain-text version that captures ONLY the most relevant information a candidate
would need to generate tailored resumes later.

Include, when present:
- Full name and contact information (email, phone, location, LinkedIn)
- A short professional summary (max 2 sentences)
- Work experience, with job titles, company names, dates and key achievements
- Education, with degrees, institutions and graduation years
- Skills and technologies (as a compact comma-separated list)
- Certifications and notable projects

Rules:
- Output plain text only. No Markdown symbols, no headings prefixes, no bullets.
- Strip any irrelevant content such as page headers/footers, repeated content or non-resume text.
- Preserve the original language of the resume (if Portuguese, output Portuguese).
- Do not invent information that is not present in the source.
- Keep it as short as possible while retaining all critical details.
PROMPT;
    }
}
