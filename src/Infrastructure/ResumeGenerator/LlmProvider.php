<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

interface LlmProvider
{
    /**
     * Send the prompt to a single LLM provider and return its raw markdown output.
     *
     * @throws RetryableProviderException on network errors, 429 or 5xx responses.
     * @throws ProviderException on non-retryable failures (4xx, unusable content).
     */
    public function complete(string $systemInstruction, string $userMessage): string;
}
