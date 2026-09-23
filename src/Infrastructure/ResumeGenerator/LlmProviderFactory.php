<?php

declare(strict_types=1);

namespace App\Infrastructure\ResumeGenerator;

use App\Application\Settings\SettingsInterface;
use GuzzleHttp\Client;

class LlmProviderFactory
{
    /**
     * @return array<string, LlmProvider>
     */
    public static function create(SettingsInterface $settings): array
    {
        $providers = [];

        $groq = $settings->get('groq');
        if ($groq['api_key'] !== '') {
            $providers['groq'] = new OpenAiCompatibleResumeGenerator(
                new Client(['timeout' => $groq['timeout']]),
                'https://api.groq.com/openai/v1/chat/completions',
                $groq['api_key'],
                $groq['model']
            );
        }

        $openrouter = $settings->get('openrouter');
        if ($openrouter['api_key'] !== '') {
            $providers['openrouter'] = new OpenAiCompatibleResumeGenerator(
                new Client(['timeout' => $openrouter['timeout']]),
                'https://openrouter.ai/api/v1/chat/completions',
                $openrouter['api_key'],
                $openrouter['model']
            );
        }

        $gemini = $settings->get('gemini');
        if ($gemini['api_key'] !== '') {
            $providers['gemini'] = new GeminiResumeGenerator(
                new Client(['timeout' => $gemini['timeout']]),
                $gemini['api_key'],
                $gemini['model']
            );
        }

        return $providers;
    }
}
