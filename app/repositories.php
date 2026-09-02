<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use App\Domain\Resume\ResumeRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\Resume\FileResumeRepository;
use App\Infrastructure\Persistence\User\InMemoryUserRepository;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(InMemoryUserRepository::class),
        ResumeRepository::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('resume');

            return new FileResumeRepository($settings['storage_path'], $settings['cache_path']);
        },
    ]);
};
