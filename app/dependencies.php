<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Infrastructure\RateLimiter\SymfonyResumeRateLimiter;
use App\Infrastructure\ResumeGenerator\GeminiResumeGenerator;
use App\Infrastructure\ResumePdfGenerator\DompdfResumePdfGenerator;
use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        ResumeGenerator::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('gemini');
            $httpClient = new Client(['timeout' => $settings['timeout']]);

            return new GeminiResumeGenerator($httpClient, $settings['api_key'], $settings['model']);
        },
        ResumePdfGenerator::class => \DI\autowire(DompdfResumePdfGenerator::class),
        ResumeRateLimiter::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('resume');

            return new SymfonyResumeRateLimiter(
                $settings['cache_path'],
                $settings['rate_limit']['requests_per_minute']
            );
        },
    ]);
};
