<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimiter;

use App\Domain\Resume\ResumeRateLimiter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

class SymfonyResumeRateLimiter implements ResumeRateLimiter
{
    private const LIMITER_ID = 'gemini_generate';

    private RateLimiterFactory $factory;

    public function __construct(string $cachePath, int $requestsPerMinute)
    {
        $cache = new FilesystemAdapter('resume_rate_limit', 0, $cachePath);
        $storage = new CacheStorage($cache);

        $this->factory = new RateLimiterFactory(
            [
                'id' => self::LIMITER_ID,
                'policy' => 'token_bucket',
                'limit' => $requestsPerMinute,
                'rate' => [
                    'interval' => '1 minute',
                    'amount' => $requestsPerMinute,
                ],
            ],
            $storage
        );
    }

    public function isAllowed(): bool
    {
        $limiter = $this->factory->create(self::LIMITER_ID);

        return $limiter->consume()->isAccepted();
    }
}
