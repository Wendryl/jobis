<?php

declare(strict_types=1);

namespace App\Domain\Resume;

interface ResumeRepository
{
    /**
     * Store the resume content (RESUME.md) to be used as the source of truth.
     */
    public function saveResumeContent(string $content): void;

    /**
     * Retrieve the stored resume content.
     *
     * @throws ResumeNotFoundException when no resume has been stored yet.
     */
    public function getResumeContent(): string;

    /**
     * Return the path to a cached PDF for the given cache key, or null if absent.
     */
    public function getCachedPdfPath(string $cacheKey): ?string;

    /**
     * Store the generated PDF under the given cache key and return its path.
     */
    public function saveCachedPdf(string $cacheKey, string $pdfContent): string;
}
