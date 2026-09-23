<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Domain\Resume\ResumeGenerationException;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Domain\Resume\ResumeUpstreamException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpTooManyRequestsException;

#[OA\Post(
    path: '/resume/generate',
    tags: ['Resume'],
    summary: 'Generate a tailored resume PDF from a job description and the base resume text',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['job_description', 'resume_content'],
            properties: [
                new OA\Property(
                    property: 'job_description',
                    description: 'The job description the resume should be tailored to (>= 20 chars).',
                    type: 'string',
                    example: 'We need an advanced Laravel developer with DDD experience.'
                ),
                new OA\Property(
                    property: 'resume_content',
                    description: 'The base resume plain text returned by POST /resume (>= 20 chars).',
                    type: 'string',
                    example: 'John Doe, Senior PHP Developer with Laravel and DDD.'
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'The tailored resume as a PDF file.',
            content: new OA\MediaType(
                mediaType: 'application/pdf',
                schema: new OA\Schema(type: 'string', format: 'binary')
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Missing or invalid "job_description" / "resume_content".'
        ),
        new OA\Response(
            response: 429,
            description: 'Rate limit exceeded. Try again later.'
        ),
        new OA\Response(
            response: 502,
            description: 'The resume could not be generated (upstream failure).'
        ),
    ]
)]
class GenerateResumeAction extends ResumeAction
{
    private ResumeGenerator $resumeGenerator;

    private ResumePdfGenerator $resumePdfGenerator;

    private ResumeRateLimiter $resumeRateLimiter;

    public function __construct(
        LoggerInterface $logger,
        ResumeGenerator $resumeGenerator,
        ResumePdfGenerator $resumePdfGenerator,
        ResumeRateLimiter $resumeRateLimiter
    ) {
        parent::__construct($logger);
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
        $resumeContent = trim(is_array($data) ? ($data['resume_content'] ?? '') : '');

        if ($jobDescription === '' || mb_strlen($jobDescription) < 20) {
            throw new HttpBadRequestException($this->request, 'A valid "job_description" (>= 20 chars) is required.');
        }

        if ($resumeContent === '' || mb_strlen($resumeContent) < 20) {
            throw new HttpBadRequestException($this->request, 'A valid "resume_content" (>= 20 chars) is required.');
        }

        if (!$this->resumeRateLimiter->isAllowed()) {
            throw new HttpTooManyRequestsException($this->request, 'Rate limit exceeded. Try again later.');
        }

        $candidateName = $this->extractCandidateName($resumeContent);

        $content = $this->resumeGenerator->generate($jobDescription, $resumeContent);

        $pdfContent = $this->resumePdfGenerator->generate($content);

        $this->logger->info('Resume PDF generated.');

        return $this->respondWithPdf($pdfContent, $candidateName);
    }

    private function extractCandidateName(string $resumeContent): string
    {
        if (preg_match('/^#\s+(.+)/m', $resumeContent, $matches)) {
            $name = trim($matches[1]);
            $name = preg_replace('/\s+/', '-', $name);
            $name = preg_replace('/[^\p{L}\-]/u', '', $name);
            return $name !== '' ? $name : 'resume';
        }

        return 'resume';
    }

    private function respondWithPdf(string $pdfContent, string $candidateName): Response
    {
        $this->response->getBody()->write($pdfContent);

        return $this->response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withHeader('Content-Disposition', sprintf('attachment; filename="%s.pdf"', $candidateName))
                    ->withHeader('Content-Length', (string) strlen($pdfContent));
    }
}
