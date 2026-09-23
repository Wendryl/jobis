# Jobis - Tailored Resume API

Jobis is a small stateless **REST API** (Slim 4, PHP 8.4) that powers a browser
extension for tailoring resumes to specific job openings. It intentionally
stores **no resume content server-side** - the browser extension is the single
source of truth for the base resume.

## How it works

1. **`POST /resume`** - The extension uploads the user's resume as a PDF. Jobis
   extracts the raw text (`smalot/pdfparser`) and sends it to an LLM that
   distills the most relevant information. The resulting plain text is returned
   in the JSON response and kept in browser storage.

2. **`POST /resume/generate`** - The extension sends the stored resume text plus
   a `job_description`. Jobis asks an LLM to write a tailored resume in Markdown,
   converts it to a PDF (`dompdf`), and returns it as an attachment.

Both steps use a **multi-provider LLM layer** with automatic fallback and retry:
providers (Groq, OpenRouter, Gemini) are tried in order, and only providers with
a configured API key are enabled. The generation endpoint is also rate limited
(Symfony RateLimiter).

Interactive documentation is available at `GET /docs` (Swagger UI) and the
OpenAPI spec at `GET /openapi.json`.

## Requirements

- PHP 8.4+
- Composer

## Setup

```bash
composer install
cp .env.example .env   # then fill in your LLM API keys
```

### Environment variables (see `.env.example`)

| Variable                     | Description                              |
|------------------------------|------------------------------------------|
| `GROQ_API_KEY`               | Enables the Groq provider                |
| `GROQ_MODEL`                 | Default: `qwen/qwen3.8-27b`              |
| `OPENROUTER_API_KEY`         | Enables the OpenRouter provider          |
| `OPENROUTER_MODEL`           | OpenRouter model                         |
| `GEMINI_API_KEY`             | Enables the Gemini provider              |
| `GEMINI_MODEL`               | Gemini model                             |
| `LLM_FALLBACK_MAX_ATTEMPTS`  | Retries per provider (default `3`)       |
| `LLM_FALLBACK_RETRY_DELAY`   | Base retry delay in seconds (default `1`)|

A provider is only enabled when its API key is set.

## Running locally

```bash
composer start        # serves on http://localhost:8080
```

## Running with Docker

```bash
docker build -t jobis .
docker run -p 8080:8080 -e GROQ_API_KEY=... -e OPENROUTER_API_KEY=... -e GEMINI_API_KEY=... jobis
```

The container runs Nginx + PHP-FPM on Alpine and logs to stdout.

## Deployment

`render.yaml` deploys the Docker image to Render.com as a web service. Set your
LLM API keys as secret env vars there.

## Tests

```bash
composer test
```

Additional quality tools:

```bash
composer exec phpstan analyse src
composer exec phpcs src app tests
```