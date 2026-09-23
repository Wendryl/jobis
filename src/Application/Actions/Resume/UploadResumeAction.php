<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Domain\Resume\ResumeExtractor;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;

#[OA\Post(
    path: '/resume',
    tags: ['Resume'],
    summary: 'Upload a base resume PDF and extract the most relevant information as plain text',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['resume'],
                properties: [
                    new OA\Property(
                        property: 'resume',
                        description: 'The base resume as a PDF file.',
                        type: 'string',
                        format: 'binary'
                    ),
                ],
                type: 'object'
            )
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'The distilled resume content as plain text.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'statusCode',
                        type: 'integer',
                        example: 200,
                    ),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'text',
                                description: 'Plain text with the most relevant resume information.',
                                type: 'string',
                                example: "John Doe\nSenior PHP Developer\nSkills: PHP, Slim, Laravel"
                            ),
                        ]
                    ),
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Missing or invalid file upload (must be a non-empty PDF).'
        ),
        new OA\Response(
            response: 502,
            description: 'The resume could not be extracted (upstream failure).'
        ),
    ]
)]
class UploadResumeAction extends ResumeAction
{
    private ResumeExtractor $resumeExtractor;

    public function __construct(LoggerInterface $logger, ResumeExtractor $resumeExtractor)
    {
        parent::__construct($logger);
        $this->resumeExtractor = $resumeExtractor;
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $files = $this->request->getUploadedFiles();
        $upload = $files['resume'] ?? null;

        if ($upload === null || $upload->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException($this->request, 'Missing valid "resume" file upload.');
        }

        $mimeType = $upload->getClientMediaType();
        if ($mimeType !== 'application/pdf') {
            throw new HttpBadRequestException($this->request, 'The uploaded resume must be a PDF file.');
        }

        $pdfContent = (string) $upload->getStream();
        if ($pdfContent === '') {
            throw new HttpBadRequestException($this->request, 'The uploaded resume is empty.');
        }

        $this->logger->info('Resume PDF received, extracting most relevant information.');

        $extractedText = $this->resumeExtractor->extractFromPdf($pdfContent);

        if (trim($extractedText) === '') {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not extract text from the uploaded resume.'
            );
        }

        return $this->respondWithData([
            'text' => $extractedText,
        ]);
    }
}
