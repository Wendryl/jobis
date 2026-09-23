<?php

declare(strict_types=1);

namespace App\Domain\Resume;

interface ResumeGenerator
{
    /**
     * Generate a tailored resume (as markdown) given the job description
     * and the source resume content.
     *
     * @throws ResumeGenerationException when the LLM call fails.
     */
    public function generate(string $jobDescription, string $resumeContent): string;
}
