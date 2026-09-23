<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    /**
     * @param int|string|null $default
     *
     * @return int|string|null
     */
    $env = static function (string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if (!is_string($value) || $value === '') {
            return $default;
        }

        return $value;
    };

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () use ($env) {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'jobis',
                    'path' => $env('docker') ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'groq' => [
                    'api_key' => $env('GROQ_API_KEY', ''),
                    'model' => $env('GROQ_MODEL', 'qwen/qwen3.8-27b'),
                    'timeout' => (int) $env('GROQ_TIMEOUT', 60),
                ],
                'openrouter' => [
                    'api_key' => $env('OPENROUTER_API_KEY', ''),
                    'model' => $env('OPENROUTER_MODEL', 'google/gemma-4-31b-it:free'),
                    'timeout' => (int) $env('OPENROUTER_TIMEOUT', 60),
                ],
                'gemini' => [
                    'api_key' => $env('GEMINI_API_KEY', ''),
                    'model' => $env('GEMINI_MODEL', 'gemini-3.8-flash'),
                    'timeout' => (int) $env('GEMINI_TIMEOUT', 120),
                ],
                'llm' => [
                    'max_attempts' => (int) $env('LLM_FALLBACK_MAX_ATTEMPTS', 3),
                    'retry_delay_seconds' => (int) $env('LLM_FALLBACK_RETRY_DELAY', 1),
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
