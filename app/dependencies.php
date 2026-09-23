<?php

declare(strict_types=1);

use App\Application\Actions\OpenApi\OpenApiJsonAction;
use App\Application\Actions\OpenApi\SwaggerUiAction;
use App\Application\Settings\SettingsInterface;
use App\Domain\Resume\ResumeExtractor;
use App\Domain\Resume\ResumeGenerator;
use App\Domain\Resume\ResumePdfGenerator;
use App\Domain\Resume\ResumeRateLimiter;
use App\Infrastructure\RateLimiter\SymfonyResumeRateLimiter;
use App\Infrastructure\ResumeExtractor\MultiProviderResumeExtractor;
use App\Infrastructure\ResumeGenerator\LlmProviderFactory;
use App\Infrastructure\ResumeGenerator\MultiProviderResumeGenerator;
use App\Infrastructure\ResumePdfGenerator\DompdfResumePdfGenerator;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser;

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
        Parser::class => \DI\autowire(Parser::class),
        ResumeExtractor::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $llm = $settings->get('llm');

            return new MultiProviderResumeExtractor(
                LlmProviderFactory::create($settings),
                $c->get(LoggerInterface::class),
                $c->get(Parser::class),
                $llm['max_attempts'],
                $llm['retry_delay_seconds']
            );
        },
        ResumeGenerator::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $llm = $settings->get('llm');

            return new MultiProviderResumeGenerator(
                LlmProviderFactory::create($settings),
                $c->get(LoggerInterface::class),
                $llm['max_attempts'],
                $llm['retry_delay_seconds']
            );
        },
        ResumePdfGenerator::class => \DI\autowire(DompdfResumePdfGenerator::class),
        ResumeRateLimiter::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('resume');

            return new SymfonyResumeRateLimiter(
                $settings['cache_path'],
                $settings['rate_limit']['requests_per_minute']
            );
        },
        OpenApiJsonAction::class => function (ContainerInterface $c) {
            return new OpenApiJsonAction(
                $c->get(LoggerInterface::class),
                new \OpenApi\Generator(),
                dirname(__DIR__) . '/src'
            );
        },
        SwaggerUiAction::class => function (ContainerInterface $c) {
            return new SwaggerUiAction(
                $c->get(LoggerInterface::class),
                '/openapi.json'
            );
        },
    ]);
};
