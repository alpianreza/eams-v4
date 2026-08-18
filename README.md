# EAMS — Laravel 13 Rebuild

Rebuild of the EAMS (Enterprise Asset & Compliance Management System) CodeIgniter 4 legacy application into a clean, modular-monolith **Laravel 13 / PHP 8.5** application.

> The legacy CI4 codebase is used only as a **functional / business-rule / data reference**. It is not the base code and is never modified.

## Baseline

- **Framework:** Laravel 13.x (`laravel/framework ^13.0`)
- **PHP:** ^8.5
- **Database:** new clean Laravel DB (MySQL/MariaDB 10.4); the production CI4 DB is only the read-only source for `php artisan eams:import`.
- **Frontend:** Blade + Bootstrap 5 + Vite (progressive enhancement).

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Verify

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
```

## Documentation (audit, decisions, architecture)

All audit, reconciliation, business-decision and architecture documents live in [`docs/`](docs/):

| Doc | Title |
|---|---|
| `docs/00`–`docs/16` | CI4 audit (Phase 0) |
| `docs/17-business-specification.md` | Canonical Business Specification |
| `docs/18-production-database-reconciliation.md` | Production DB Reconciliation |
| `docs/19-decision-log.md` | Decision Log (Human-approved) |
| `docs/20-laravel-architecture.md` | Laravel Architecture (DRAFT) |

## Status

See **Milestone** notes in the docs and `docs/20-laravel-architecture.md` for implementation status (IMPLEMENTED / IN PROGRESS / TODO).
