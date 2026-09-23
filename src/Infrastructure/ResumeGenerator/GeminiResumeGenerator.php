<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class GeminiResumeGenerator implements LlmProvider
{
    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    private Client $httpClient;

    private string $apiKey;

    private string $model;

    private int $maxOutputTokens;

    public function __construct(Client $httpClient, string $apiKey, string $model, int $maxOutputTokens = 4096)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->maxOutputTokens = $maxOutputTokens;
    }

    public function complete(string $systemInstruction, string $userMessage): string
    {
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
                            'maxOutputTokens' => $this->maxOutputTokens,
                        ],
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw $this->toProviderException($e);
        }

        $body = json_decode((string) $response->getBody(), true);

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new ProviderException('Gemini returned no usable content.');
        }

        return $text;
    }

    private function toProviderException(GuzzleException $e): \Exception
    {
        $status = $e instanceof BadResponseException ? $e->getResponse()->getStatusCode() : null;
        $message = 'Gemini API request failed: ' . $e->getMessage();

        if ($status === null || $status === 429 || $status >= 500) {
            return new RetryableProviderException($message, 0, $e);
        }

        return new ProviderException($message, 0, $e);
    }
}
