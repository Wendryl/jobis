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
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'gemini' => [
                    'api_key' => $_ENV['GEMINI_API_KEY'] ?? '',
                    'model' => $_ENV['GEMINI_MODEL'] ?? 'gemini-2.0-flash',
                    'timeout' => 120,
                ],
                'resume' => [
                    'storage_path' => __DIR__ . '/../var/resumes',
                    'cache_path' => __DIR__ . '/../var/cache/resumes',
                    'rate_limit' => [
                        'requests_per_minute' => 10,
                    ],
                ],
            ]);
        }
    ]);
};
