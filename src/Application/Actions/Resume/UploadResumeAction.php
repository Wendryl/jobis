<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class UploadResumeAction extends ResumeAction
{
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

        $content = (string) $upload->getStream();
        if (trim($content) === '') {
            throw new HttpBadRequestException($this->request, 'The uploaded resume is empty.');
        }

        $this->resumeRepository->saveResumeContent($content);

        $this->logger->info('Resume was uploaded.');

        return $this->respondWithData([
            'message' => 'Resume stored successfully.',
        ]);
    }
}
