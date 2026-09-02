<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Application\Actions\Action;
use App\Domain\Resume\ResumeRepository;
use Psr\Log\LoggerInterface;

abstract class ResumeAction extends Action
{
    protected ResumeRepository $resumeRepository;

    public function __construct(LoggerInterface $logger, ResumeRepository $resumeRepository)
    {
        parent::__construct($logger);
        $this->resumeRepository = $resumeRepository;
    }
}
