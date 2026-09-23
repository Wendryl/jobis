<?php

declare(strict_types=1);

namespace App\Domain\Resume;

interface ResumeExtractor
{
    /**
     * Extract the most relevant information from a resume PDF and return it as plain text.
     *
     * @param string $pdfContent Raw PDF binary content.
     */
    public function extractFromPdf(string $pdfContent): string;
}
