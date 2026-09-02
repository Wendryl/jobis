<?php

declare(strict_types=1);

namespace App\Domain\Resume;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ResumeNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'No resume has been uploaded yet. Upload one via POST /resume.';
}
