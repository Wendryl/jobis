<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumePdfGenerator;

use App\Domain\Resume\ResumeGenerationException;
use App\Domain\Resume\ResumePdfGenerator;
use Dompdf\Dompdf;
use Dompdf\Options;
use League\CommonMark\CommonMarkConverter;
use Throwable;

class DompdfResumePdfGenerator implements ResumePdfGenerator
{
    private Dompdf $dompdf;

    private CommonMarkConverter $markdownConverter;

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $this->dompdf = new Dompdf($options);
        $this->markdownConverter = new CommonMarkConverter();
    }

    public function generate(string $tailoredResumeMarkdown): string
    {
        $html = $this->markdownConverter->convert($tailoredResumeMarkdown)->getContent();

        $document = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #222; }
    h1 { font-size: 18pt; margin: 0 0 4pt 0; }
    h2 { font-size: 13pt; border-bottom: 1px solid #999; padding-bottom: 2pt; margin: 14pt 0 6pt 0; }
    h3 { font-size: 11pt; margin: 10pt 0 4pt 0; }
    p, li { line-height: 1.35; }
    ul { margin: 4pt 0; }
</style>
</head>
<body>
{$html}
</body>
</html>
HTML;

        try {
            $this->dompdf->loadHtml($document);
            $this->dompdf->render();
            return $this->dompdf->output();
        } catch (Throwable $e) {
            throw new ResumeGenerationException('PDF generation failed: ' . $e->getMessage());
        }
    }
}
