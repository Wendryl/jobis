<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Resume;

use App\Domain\Resume\ResumeNotFoundException;
use App\Domain\Resume\ResumeRepository;

class FileResumeRepository implements ResumeRepository
{
    private const RESUME_FILENAME = 'RESUME.md';

    private const PDF_EXTENSION = '.pdf';

    private string $storagePath;

    private string $cachePath;

    public function __construct(string $storagePath, string $cachePath)
    {
        $this->storagePath = $storagePath;
        $this->cachePath = $cachePath;
    }

    public function saveResumeContent(string $content): void
    {
        $this->ensureDirectory($this->storagePath);
        file_put_contents($this->resumeFilePath(), $content);
    }

    public function getResumeContent(): string
    {
        $path = $this->resumeFilePath();

        if (!is_file($path)) {
            throw new ResumeNotFoundException();
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new ResumeNotFoundException();
        }

        return $content;
    }

    public function getCachedPdfPath(string $cacheKey): ?string
    {
        $path = $this->cachePath . '/' . $cacheKey . self::PDF_EXTENSION;

        return is_file($path) ? $path : null;
    }

    public function saveCachedPdf(string $cacheKey, string $pdfContent): string
    {
        $this->ensureDirectory($this->cachePath);
        $path = $this->cachePath . '/' . $cacheKey . self::PDF_EXTENSION;
        file_put_contents($path, $pdfContent);

        return $path;
    }

    private function resumeFilePath(): string
    {
        return $this->storagePath . '/' . self::RESUME_FILENAME;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
