# AGENTS.md - Project Context for Jobis

## Project Overview
- **Name:** jobis
- **Type:** REST API
- **Framework:** Slim 4
- **Database:** None (for now)
- **Location:** `/home/wendryl/Área de trabalho/jobis`
- **Created:** 2026-09-02

## Environment
- **OS:** Linux (Debian-based)
- **PHP:** 8.4.24
- **Composer:** Available

## Setup Notes
- Project created with `composer create-project slim/slim-skeleton`
- **PHP 8.4 fix applied:** Updated `php-di/php-di` from `^6.4` to `^7.0` to fix implicit nullable deprecation error on PHP 8.4+

## Project Structure
```
jobis/
├── app/
│   ├── dependencies.php
│   ├── middleware.php
│   ├── repositories.php
│   ├── routes.php
│   └── settings.php
├── public/
│   ├── .htaccess
│   └── index.php                    # Entry point
├── src/
│   ├── Application/
│   │   ├── Actions/
│   │   │   ├── Action.php
│   │   │   ├── ActionError.php
│   │   │   ├── ActionPayload.php
│   │   │   └── User/
│   │   │       ├── ListUsersAction.php
│   │   │       ├── UserAction.php
│   │   │       └── ViewUserAction.php
│   │   ├── Handlers/
│   │   │   ├── HttpErrorHandler.php
│   │   │   └── ShutdownHandler.php
│   │   ├── Middleware/
│   │   │   └── SessionMiddleware.php
│   │   ├── ResponseEmitter/
│   │   │   └── ResponseEmitter.php
│   │   └── Settings/
│   │       ├── Settings.php
│   │       └── SettingsInterface.php
│   ├── Domain/
│   │   ├── DomainException/
│   │   │   ├── DomainException.php
│   │   │   └── DomainRecordNotFoundException.php
│   │   └── User/
│   │       ├── User.php
│   │       ├── UserNotFoundException.php
│   │       └── UserRepository.php
│   └── Infrastructure/
│       └── Persistence/
│           └── User/
│               └── InMemoryUserRepository.php
├── tests/
│   ├── TestCase.php
│   ├── bootstrap.php
│   ├── Application/
│   │   └── Actions/
│   │       ├── ActionTest.php
│   │       └── User/
│   │           ├── ListUserActionTest.php
│   │           └── ViewUserActionTest.php
│   ├── Domain/
│   │   └── User/
│   │       └── UserTest.php
│   └── Infrastructure/
│       └── Persistence/
│           └── User/
│               └── InMemoryUserRepositoryTest.php
├── composer.json
├── composer.lock
├── docker-compose.yml
├── phpcs.xml
├── phpstan.neon.dist
├── phpunit.xml
├── .github/
│   └── workflows/
│       └── tests.yml
└── AGENTS.md                          # This file
```

## Useful Commands
```bash
# Start dev server
php -S localhost:8080 -t public

# Install dependencies
composer install

# Update dependencies
composer update
```

## Current Status
- [x] Project created
- [x] PHP 8.4 compatibility fixed
- [x] REST API routes defined
- [x] User actions implemented (List, View, CRUD)
