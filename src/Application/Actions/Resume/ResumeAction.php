<?php

declare(strict_types=1);

namespace App\Application\Actions\Resume;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;

abstract class ResumeAction extends Action
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }
}
