<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'jobis',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'groq' => [
                    'api_key' => $_ENV['GROQ_API_KEY'] ?? '',
                    'model' => $_ENV['GROQ_MODEL'] ?? 'qwen/qwen3.8-27b',
                    'timeout' => (int) ($_ENV['GROQ_TIMEOUT'] ?? 60),
                ],
                'openrouter' => [
                    'api_key' => $_ENV['OPENROUTER_API_KEY'] ?? '',
                    'model' => $_ENV['OPENROUTER_MODEL'] ?? 'google/gemma-4-31b-it:free',
                    'timeout' => (int) ($_ENV['OPENROUTER_TIMEOUT'] ?? 60),
                ],
                'gemini' => [
                    'api_key' => $_ENV['GEMINI_API_KEY'] ?? '',
                    'model' => $_ENV['GEMINI_MODEL'] ?? 'gemini-3.8-flash',
                    'timeout' => (int) ($_ENV['GEMINI_TIMEOUT'] ?? 120),
                ],
                'llm' => [
                    'max_attempts' => (int) ($_ENV['LLM_FALLBACK_MAX_ATTEMPTS'] ?? 3),
                    'retry_delay_seconds' => (int) ($_ENV['LLM_FALLBACK_RETRY_DELAY'] ?? 1),
                ],
                'resume' => [
                    'cache_path' => __DIR__ . '/../var/cache/rate_limit',
                    'rate_limit' => [
                        'requests_per_minute' => 10,
                    ],
                ],
            ]);
        }
    ]);
};
