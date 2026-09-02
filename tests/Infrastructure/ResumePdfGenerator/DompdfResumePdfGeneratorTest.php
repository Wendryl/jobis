<?php

declare(strict_types=1);

namespace Tests\Infrastructure\ResumePdfGenerator;

use App\Infrastructure\ResumePdfGenerator\DompdfResumePdfGenerator;
use PHPUnit\Framework\TestCase;

class DompdfResumePdfGeneratorTest extends TestCase
{
    public function testGenerateReturnsPdfBytes()
    {
        $generator = new DompdfResumePdfGenerator();

        $pdf = $generator->generate("# John Doe\n\n- PHP\n- Laravel");

        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
