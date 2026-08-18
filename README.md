# EAMS v4 — Laravel Rebuild

Repository untuk membangun ulang **EAMS (Enterprise Asset & Compliance Management System)** dari CodeIgniter 4 ke Laravel.

## Status: Fase 0 (Audit) · Fase 0.5 (Review & Konsolidasi) · Fase 0.6 (Rekonsiliasi Production DB) — selesai (READ-ONLY)

Seluruh hasil audit atas repo legacy [`alpianreza/eams`](https://github.com/alpianreza/eams) terdokumentasi di folder [`docs/`](./docs):

| # | Dokumen |
|---|---|
| 00 | [Audit Overview](./docs/00-audit-overview.md) |
| 01 | [System Architecture](./docs/01-system-architecture.md) |
| 02 | [Modules](./docs/02-modules.md) |
| 03 | [Database (Production-Verified)](./docs/03-database.md) |
| 04 | [Routes](./docs/04-routes.md) |
| 05 | [Controllers](./docs/05-controllers.md) |
| 06 | [Models & Entities](./docs/06-models-entities.md) |
| 07 | [Views & UI](./docs/07-views-ui.md) |
| 08 | [JavaScript & AJAX](./docs/08-javascript-ajax.md) |
| 09 | [Business Rules](./docs/09-business-rules.md) |
| 10 | [Checklist Rules](./docs/10-checklist-rules.md) |
| 11 | [Reports & PDF](./docs/11-reports-pdf.md) |
| 12 | [Auth & Authorization](./docs/12-auth-authorization.md) |
| 13 | [Dependencies](./docs/13-dependencies.md) |
| 14 | [Technical Debt](./docs/14-technical-debt.md) |
| 15 | [Ambiguities / Need Decision (Final Decision List)](./docs/15-ambiguities-need-decision.md) |
| 16 | [Laravel Migration Considerations](./docs/16-laravel-migration-considerations.md) |
| 17 | [Business Specification (Canonical)](./docs/17-business-specification.md) |
| 18 | [Production Database Reconciliation](./docs/18-production-database-reconciliation.md) |

> Audit 2026-08-16 (read-only, repo legacy tidak diubah) · Review & konsolidasi 2026-08-18 · Rekonsiliasi production DB (`eams_database.sql`, MariaDB 10.4.32) 2026-08-18.
> Sebelum mulai coding Laravel: selesaikan 21 keputusan `NEED HUMAN DECISION` tersisa di [docs/15](./docs/15-ambiguities-need-decision.md) — 3 Critical: Q-004 (engine status periode), Q-006 (checked_by), Q-007 (sumber kebenaran PIC). (5 item sudah RESOLVED BY PRODUCTION SCHEMA berkat `eams_database.sql`.)
