# AGENTS.md - Project Context for Jobis

## Project Overview
- **Name:** jobis
- **Type:** REST API
- **Framework:** Slim 4
- **Database:** None (stateless API - no server-side resume storage)
- **Location:** `/home/wendryl/Área de trabalho/jobis`
- **Created:** 2026-09-02

## Environment
- **OS:** Linux (Debian-based)
- **PHP:** 8.4.24
- **Composer:** Available

## Setup Notes
- Project created with `composer create-project slim/slim-skeleton`
- **PHP 8.4 fix applied:** Updated `php-di/php-di` from `^6.4` to `^7.0` to fix implicit nullable deprecation error on PHP 8.4+
- Environment loaded from `.env` via `vlucas/phpdotenv` (`safeLoad()` in `public/index.php`)

## Configuration (env vars, see `.env.example`)
- `GROQ_API_KEY` / `GROQ_MODEL` / `GROQ_TIMEOUT` - Groq provider (`qwen/qwen3.8-27b` default)
- `OPENROUTER_API_KEY` / `OPENROUTER_MODEL` / `OPENROUTER_TIMEOUT` - OpenRouter provider
- `GEMINI_API_KEY` / `GEMINI_MODEL` / `GEMINI_TIMEOUT` - Gemini provider
- `LLM_FALLBACK_MAX_ATTEMPTS` / `LLM_FALLBACK_RETRY_DELAY` - retry/fallback behavior
- A provider is only enabled when its API key is set; also injectable in tests

## API Flow
1. `POST /resume` - Browser extension uploads a PDF resume. Server extracts text from the PDF (`smalot/pdfparser`), sends the raw text to an LLM to distill the most relevant information, and returns it as plain text in the JSON response.
2. `POST /resume/generate` - Browser extension sends `job_description` and `resume_content` (the plain text from step 1, stored in browser storage). Server generates a tailored resume via an LLM and returns a PDF.

No resume content is stored server-side. The browser extension is the source of truth for the base resume.

## Project Structure
```
jobis/
├── app/
│   ├── dependencies.php
│   ├── middleware.php
│   ├── routes.php
│   └── settings.php
├── public/
│   ├── .htaccess
│   └── index.php                    # Entry point (loads .env via phpdotenv)
├── docker/
│   ├── nginx.conf                   # Nginx config (inline ${PORT} substitution)
│   ├── php-fpm.conf
│   └── start.sh                     # Container entrypoint (php-fpm + nginx)
├── src/
│   ├── Application/
│   │   ├── Actions/
│   │   │   ├── Action.php
│   │   │   ├── ActionError.php
│   │   │   ├── ActionPayload.php
│   │   │   ├── OpenApi/
│   │   │   │   ├── OpenApiJsonAction.php   # GET /openapi.json
│   │   │   │   └── SwaggerUiAction.php     # GET /docs
│   │   │   └── Resume/
│   │   │       ├── ResumeAction.php
│   │   │       ├── UploadResumeAction.php
│   │   │       └── GenerateResumeAction.php
│   │   ├── Handlers/
│   │   │   ├── HttpErrorHandler.php
│   │   │   └── ShutdownHandler.php
│   │   ├── Middleware/
│   │   │   └── SessionMiddleware.php
│   │   ├── OpenApi/
│   │   │   └── OpenApi.php                # OpenAPI info attributes
│   │   ├── ResponseEmitter/
│   │   │   └── ResponseEmitter.php
│   │   └── Settings/
│   │       ├── Settings.php
│   │       └── SettingsInterface.php
│   ├── Domain/
│   │   ├── DomainException/
│   │   │   ├── DomainException.php
│   │   │   └── DomainRecordNotFoundException.php
│   │   └── Resume/
│   │       ├── ResumeExtractor.php
│   │       ├── ResumeGenerationException.php
│   │       ├── ResumeGenerator.php
│   │       ├── ResumePdfGenerator.php
│   │       ├── ResumeRateLimiter.php
│   │       └── ResumeUpstreamException.php
│   └── Infrastructure/
│       ├── RateLimiter/
│       │   └── SymfonyResumeRateLimiter.php
│       ├── ResumeExtractor/
│       │   └── MultiProviderResumeExtractor.php
│       ├── ResumeGenerator/
│       │   ├── GeminiResumeGenerator.php
│       │   ├── LlmProvider.php
│       │   ├── LlmProviderFactory.php
│       │   ├── MultiProviderResumeGenerator.php
│       │   ├── OpenAiCompatibleResumeGenerator.php
│       │   ├── ProviderException.php
│       │   └── RetryableProviderException.php
│       └── ResumePdfGenerator/
│           └── DompdfResumePdfGenerator.php
├── tests/
│   ├── TestCase.php
│   ├── bootstrap.php
│   ├── Application/
│   │   └── Actions/
│   │       ├── ActionTest.php
│   │       ├── OpenApi/
│   │       │   └── OpenApiActionTest.php
│   │       └── Resume/
│   │           ├── ResumeActionTestCase.php
│   │           ├── UploadResumeActionTest.php
│   │           ├── GenerateResumeActionTest.php
│   │           └── fixtures/
│   │               └── fake.pdf
│   └── Infrastructure/
│       ├── ResumeGenerator/
│       │   └── MultiProviderResumeGeneratorTest.php
│       └── ResumePdfGenerator/
│           └── DompdfResumePdfGeneratorTest.php
├── logs/
│   └── app.log                     # App log (php://stdout when running in Docker)
├── var/
│   └── cache/
│       ├── rate_limit/             # Symfony rate limiter cache
│       └── resumes/                # Generated PDFs (runtime artifacts, not persisted)
├── .env / .env.example             # LLM keys & fallback tuning
├── Dockerfile
├── .dockerignore
├── render.yaml                     # Render.com deployment config
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

# Run tests
composer test

# Run static analysis
composer exec phpstan analyse src

# Run code style checks
composer exec phpcs src app tests
```

## Current Status
- [x] Project created
- [x] PHP 8.4 compatibility fixed
- [x] REST API routes defined
- [x] Stateless resume processing (PDF extraction + tailored generation)
- [x] Multi-provider LLM fallback (Groq, OpenRouter, Gemini), configurable via env vars
- [x] Rate limiting on generation endpoint (configurable via settings)
- [x] Docker deployment (Nginx + PHP-FPM, Alpine) and Render.com config
- [x] User domain/actions removed (unused)
- [x] Server-side resume storage removed