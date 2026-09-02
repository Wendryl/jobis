<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Resume;

use App\Domain\Resume\ResumeNotFoundException;
use App\Infrastructure\Persistence\Resume\FileResumeRepository;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class FileResumeRepositoryTest extends TestCase
{
    private string $tmp;

    private FileResumeRepository $repository;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/resume_test_' . uniqid();
        mkdir($this->tmp . '/storage', 0777, true);
        mkdir($this->tmp . '/cache', 0777, true);

        $this->repository = new FileResumeRepository(
            $this->tmp . '/storage',
            $this->tmp . '/cache'
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmp)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tmp, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->tmp);
        }
    }

    public function testSaveAndGetResumeContent()
    {
        $content = "# John Doe\n\nSenior PHP Developer";
        $this->repository->saveResumeContent($content);

        $this->assertSame($content, $this->repository->getResumeContent());
    }

    public function testGetResumeContentThrowsWhenMissing()
    {
        $this->expectException(ResumeNotFoundException::class);
        $this->repository->getResumeContent();
    }

    public function testCacheRoundTrip()
    {
        $this->assertNull($this->repository->getCachedPdfPath('abc'));

        $path = $this->repository->saveCachedPdf('abc', '%PDF-1.4 fake');

        $this->assertSame($path, $this->repository->getCachedPdfPath('abc'));
        $this->assertSame('%PDF-1.4 fake', file_get_contents($path));
    }
}
