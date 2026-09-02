<?php

declare(strict_types=1);

namespace App\Domain\Resume;

interface ResumeRateLimiter
{
    /**
     * Attempt to consume one of the allowed LLM requests.
     * Returns true if permitted, false if the limit has been hit.
     */
    public function isAllowed(): bool;
}
