<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumeUpstreamException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GeminiResumeGenerator implements ResumeGenerator
{
    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    private Client $httpClient;

    private string $apiKey;

    private string $model;

    public function __construct(Client $httpClient, string $apiKey, string $model)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generate(string $jobDescription, string $resumeContent): string
    {
        $systemInstruction = $this->systemInstruction();
        $userMessage = $this->buildUserMessage($jobDescription, $resumeContent);

        try {
            $response = $this->httpClient->post(
                sprintf(self::API_URL_TEMPLATE, $this->model) . '?key=' . $this->apiKey,
                [
                    'json' => [
                        'systemInstruction' => [
                            'parts' => ['text' => $systemInstruction],
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => ['text' => $userMessage],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.5,
                            'maxOutputTokens' => 4096,
                        ],
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new ResumeUpstreamException('Gemini API request failed: ' . $e->getMessage());
        }

        $body = json_decode((string) $response->getBody(), true);

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new ResumeUpstreamException('Gemini returned no usable content.');
        }

        return $text;
    }

    private function systemInstruction(): string
    {
        return <<<'PROMPT'
You are an expert resume writer. Given a job description and a source resume written in
Markdown, produce a single tailored resume in Markdown that maximizes the candidate's
chances of getting an interview for that specific job.

Rules:
- Keep factual claims grounded in the source resume. Do not invent experience.
- Reorder and emphasize the most relevant skills, experience and achievements for the job.
- Mirror the technical keywords and requirements listed in the job description.
- Match the language of the job description (if the job description is Portuguese, write in Portuguese).
- Structure the output with clear Markdown headings and bullet points.
- Output ONLY the resume Markdown, with no preamble or commentary.
- Try to keep it concise and short, preferably a single page resume.
PROMPT;
    }

    private function buildUserMessage(string $jobDescription, string $resumeContent): string
    {
        return "JOB DESCRIPTION\n===============\n{$jobDescription}\n\n"
            . "SOURCE RESUME\n=============\n{$resumeContent}";
    }
}
