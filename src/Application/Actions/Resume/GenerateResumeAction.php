<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Domain\Resume\ResumeGenerationException;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Domain\Resume\ResumeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpTooManyRequestsException;

class GenerateResumeAction extends ResumeAction
{
    private ResumeGenerator $resumeGenerator;

    private ResumePdfGenerator $resumePdfGenerator;

    private ResumeRateLimiter $resumeRateLimiter;

    public function __construct(
        LoggerInterface $logger,
        ResumeRepository $resumeRepository,
        ResumeGenerator $resumeGenerator,
        ResumePdfGenerator $resumePdfGenerator,
        ResumeRateLimiter $resumeRateLimiter
    ) {
        parent::__construct($logger, $resumeRepository);
        $this->resumeGenerator = $resumeGenerator;
        $this->resumePdfGenerator = $resumePdfGenerator;
        $this->resumeRateLimiter = $resumeRateLimiter;
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $data = $this->getFormData();
        $jobDescription = trim(is_array($data) ? ($data['job_description'] ?? '') : '');

        if ($jobDescription === '' || mb_strlen($jobDescription) < 20) {
            throw new HttpBadRequestException($this->request, 'A valid "job_description" (>= 20 chars) is required.');
        }

        $resumeContent = $this->resumeRepository->getResumeContent();

        $cacheKey = $this->buildCacheKey($jobDescription, $resumeContent);

        $cachedPath = $this->resumeRepository->getCachedPdfPath($cacheKey);
        if ($cachedPath !== null) {
            $this->logger->info('Serving cached resume PDF.');
            return $this->respondWithPdf(file_get_contents($cachedPath));
        }

        if (!$this->resumeRateLimiter->isAllowed()) {
            throw new HttpTooManyRequestsException($this->request, 'Rate limit exceeded. Try again later.');
        }

        $content = $this->resumeGenerator->generate($jobDescription, $resumeContent);

        $pdfContent = $this->resumePdfGenerator->generate($content);

        $this->resumeRepository->saveCachedPdf($cacheKey, $pdfContent);

        $this->logger->info('Resume PDF generated.');

        return $this->respondWithPdf($pdfContent);
    }

    private function buildCacheKey(string $jobDescription, string $resumeContent): string
    {
        return hash('sha256', $this->normalize($jobDescription . "\n" . $resumeContent));
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
    }

    private function respondWithPdf(string $pdfContent): Response
    {
        $this->response->getBody()->write($pdfContent);

        return $this->response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withHeader('Content-Disposition', 'attachment; filename="resume.pdf"')
                    ->withHeader('Content-Length', (string) strlen($pdfContent));
    }
}
