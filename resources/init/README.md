# About Semitexa

"Make it work, make it right, make it fast." — Kent Beck

Semitexa isn't just a framework; it's a philosophy of efficiency.
Engineered for the high-performance Swoole ecosystem and built with an AI-first mindset,
it allows you to stop fighting the infrastructure and start building the future.

Simple by design. Powerful by nature.

## Requirements

- Docker and Docker Compose
- Composer (on host for install)

## Install

From an empty folder (get the framework and install dependencies):

```bash
composer require semitexa/core
```

From a clone or existing project (dependencies already in `composer.json`):

```bash
composer install
```

Then:

```bash
cp .env.example .env
```

## Run (Docker — supported way)

```bash
bin/semitexa server:start
```

To stop:

```bash
bin/semitexa server:stop
```

Default URL: **http://0.0.0.0:{{ default_swoole_port }}** (configurable via `.env` `SWOOLE_PORT`).

## Documentation

All framework documentation lives in `vendor/` (installed with Composer). Open these from the project root:

| Topic | File or folder |
|-------|----------------|
| **Running the app** — Docker, ports, logs | [vendor/semitexa/core/docs/RUNNING.md](vendor/semitexa/core/docs/RUNNING.md) |
| **Adding pages and routes** — modules, Request/Handler | [vendor/semitexa/core/docs/ADDING_ROUTES.md](vendor/semitexa/core/docs/ADDING_ROUTES.md) |
| **Attributes** — AsPayload, AsPayloadHandler, AsResource, etc. | [vendor/semitexa/core/docs/attributes/README.md](vendor/semitexa/core/docs/attributes/README.md) |
| **Service contracts** — contracts:list, active implementation | [vendor/semitexa/core/docs/SERVICE_CONTRACTS.md](vendor/semitexa/core/docs/SERVICE_CONTRACTS.md) |
| **Package map & conventions** (if semitexa/docs is installed) | [vendor/semitexa/docs/README.md](vendor/semitexa/docs/README.md) · [vendor/semitexa/docs/guides/CONVENTIONS.md](vendor/semitexa/docs/guides/CONVENTIONS.md) |

In your editor you can open these paths directly (e.g. Ctrl+P → paste path). No `docs/` folder in the project root — everything is in vendor.

## Structure

- `src/modules/` – your application modules (add new pages and endpoints here). New routes only in modules.
- `var/docs/` – working directory for notes and drafts; not committed.
- `AI_ENTRY.md` – entry point for AI assistants; `AI_NOTES.md` – your notes (never overwritten).

## Tests

```bash
composer require --dev phpunit/phpunit
```

```bash
vendor/bin/phpunit
```

Use `phpunit.xml.dist`; add tests in `tests/`.
