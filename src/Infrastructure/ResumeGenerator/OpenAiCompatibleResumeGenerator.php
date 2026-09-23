<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;

class OpenAiCompatibleResumeGenerator implements LlmProvider
{
    private Client $httpClient;

    private string $apiUrl;

    private string $apiKey;

    private string $model;

    private int $maxTokens;

    public function __construct(
        Client $httpClient,
        string $apiUrl,
        string $apiKey,
        string $model,
        int $maxTokens = 4096
    ) {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->maxTokens = $maxTokens;
    }

    public function complete(string $systemInstruction, string $userMessage): string
    {
        try {
            $response = $this->httpClient->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'max_tokens' => $this->maxTokens,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw $this->toProviderException($e);
        }

        $body = json_decode((string) $response->getBody(), true);

        $text = $body['choices'][0]['message']['content'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new ProviderException($this->apiUrl . ' returned no usable content.');
        }

        return $text;
    }

    private function toProviderException(GuzzleException $e): \Exception
    {
        $status = $e instanceof BadResponseException ? $e->getResponse()->getStatusCode() : null;
        $message = $this->apiUrl . ' request failed: ' . $e->getMessage();

        if ($status === null || $status === 429 || $status >= 500) {
            return new RetryableProviderException($message, 0, $e);
        }

        return new ProviderException($message, 0, $e);
    }
}
