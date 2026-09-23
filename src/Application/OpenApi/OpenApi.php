<?php

declare(strict_types=1);

namespace App\Application\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Jobis API',
    description: <<<'DESC'
Stateless API used by the Jobis browser extension.

Flow:
1. `POST /resume` — upload your base resume **PDF**; the server extracts the most relevant
   information and returns it as plain text.
2. Store that text in the extension storage and send it back together with the job
   description on `POST /resume/generate` to receive a tailored resume **PDF**.

No resume content is stored server-side.
DESC
)]
#[OA\Server(
    url: '/',
    description: 'API server'
)]
class OpenApi
{
}
