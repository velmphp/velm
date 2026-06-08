---
sidebar_position: 5
---

# Production operations

Guide for running Velm (`velmphp/app`) beyond local development: database choice, attachments, scheduled jobs, and module lifecycle.

## Database

Velm uses Laravel’s database connection (`DB_CONNECTION`). The ORM runs on the same connection as Laravel migrations.

| Engine | Status | Notes |
|--------|--------|-------|
| **SQLite** | Supported | Default in `velmphp/app`; fine for small deployments and CI |
| **MySQL / MariaDB** | Supported | Recommended for production; use `utf8mb4` |
| **PostgreSQL** | Supported | Recommended for production |

Set `DB_*` in `.env`, run Laravel migrations, then:

```bash
php artisan velm:migrate
```

Use `php artisan velm:migrate:fresh --yes` only in **local/staging** — it drops all Velm-owned tables and reinstalls bootstrap modules.

## Attachments and files

Attachments (`ir.attachment`) use Laravel Flysystem. Configure the disk in `config/filesystems.php` and point Velm at it via `config/velm.php` (attachment disk / path settings).

- Use a **persistent disk** in production (S3, local NFS, etc.) — not ephemeral container storage.
- The **file manager** module stores folder metadata in the database; binary content lives on the configured disk.
- Back up both the database and the attachment disk together.

## Cron and background work

Workflow escalations and other Velm crons register in Laravel’s scheduler. On the server:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Run pending Velm server actions manually when debugging:

```bash
php artisan velm:cron:run
```

Use a real queue worker (`php artisan queue:work`) if you add queued jobs alongside Velm.

## Module install / upgrade / uninstall

| Command | Purpose |
|---------|---------|
| `php artisan velm:migrate` | Install or upgrade modules from manifest state |
| `php artisan velm:module:sync {module}` | Re-sync views/menus/schema for one module |
| `php artisan velm:module:uninstall {module}` | Remove install state and UI metadata (tables kept) |
| `php artisan velm:module:uninstall {module} --drop-schema` | **Dev/local only** — also drops tables owned exclusively by the module |

`--drop-schema` is blocked outside `local` and `testing` environments. Never use it on production databases with data you need to keep.

## Environment variables

| Variable | Purpose |
|----------|---------|
| `VELM_ADMIN_EMAIL` / `VELM_ADMIN_PASSWORD` | Bootstrap admin user on first `velm:migrate` |
| `VELM_APP_NAME` / `VELM_LOGO_*` | Panel branding |
| `VELM_MENU_LAYOUT` | `apps` (default) or `sidebar` |
| `VELM_AUDIT_DSN` | Optional dedicated database URL for IT audit tables (empty = main app database) |
| `VELM_AUDIT_RETENTION_DAYS` | Keep `system_audit` logs (`ir.audit.log`, `ir.login.log`, `ir.user.lifecycle`) for this many days before the daily cron purges them |

See [installation](./installation.md) and [addons](./addons.md) for addon paths and drop-in Laravel setup.

## Health checks

After deploy:

1. `php artisan migrate --force && php artisan velm:migrate`
2. Open `/velm` and confirm login
3. Apps catalog shows expected modules as **Installed**
4. Upload a test attachment and confirm it persists across redeploy

## Versions

Stable **1.0.0** is the default on Packagist:

```bash
composer create-project velmphp/app my_app
```

Pin an exact tag when you want reproducible deploys:

```bash
composer create-project velmphp/app my_app v1.0.0
```

Upgrade an existing project with `composer update` after changing version constraints in `composer.json`. Run `php artisan velm:migrate` after pulling framework or module updates.
