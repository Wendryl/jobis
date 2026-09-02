<?php

declare(strict_types=1);

namespace App\Domain\Resume;

interface ResumePdfGenerator
{
    /**
     * Render a markdown resume into PDF bytes.
     *
     * @throws ResumeGenerationException when PDF rendering fails.
     */
    public function generate(string $tailoredResumeMarkdown): string;
}
