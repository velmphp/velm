# Velm

<p align="center">
  <a href="https://github.com/velmphp/velm/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/velmphp/velm/ci.yml?branch=main&style=for-the-badge&logo=github&label=CI" alt="CI"></a>
  <a href="https://codecov.io/gh/velmphp/velm"><img src="https://img.shields.io/codecov/c/github/velmphp/velm?branch=main&style=for-the-badge&logo=codecov&label=coverage" alt="Test coverage"></a>
  <a href="https://packagist.org/packages/velmphp/framework"><img src="https://img.shields.io/packagist/v/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=Packagist" alt="Packagist version"></a>
  <a href="https://packagist.org/packages/velmphp/framework/stats"><img src="https://img.shields.io/packagist/dt/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=downloads" alt="Packagist downloads"></a>
</p>

<p align="center">
  <a href="https://www.php.net/releases/8.3.php"><img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License MIT"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 4"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-4-6ADE80?style=for-the-badge&logo=pest&logoColor=white" alt="Pest 4"></a>
</p>

<p align="center">
  <strong>Odoo's semantics. Laravel's ergonomics. Filament's craft.</strong>
</p>

<p align="center">
  Velm is a PHP ERP framework built on <a href="https://laravel.com">Laravel</a>, <a href="https://livewire.laravel.com">Livewire</a>, and <a href="https://filamentphp.com">Filament</a>—installable addons per database, a recordset ORM, and declarative view arch that resolves into Filament tables and forms. It extends the stack you already use (Composer, Pest, Eloquent connections, the scheduler) with Odoo-style modularity, not a parallel platform.
</p>

<p align="center">
  Semantic port of <a href="https://github.com/coolsam726/pyvelm">PyVelm</a>; packages ship as <code>velmphp/*</code> alongside your app.
</p>

- **Organization:** [github.com/velmphp](https://github.com/velmphp)
- **Composer vendor:** `velmphp/*`
- **PHP namespace:** `Velm\`

## Architecture plan

The full feasibility and implementation plan lives in **[PLAN.md](./PLAN.md)** — read this first for module system, ORM, views, migrations, CLI, packages, and phased delivery.

Current progress: **[ROADMAP.md](./ROADMAP.md)**.

## Documentation

Module-author guides (models, `$inherit`, `static::super()`) live in the **[Docusaurus site](./website/)** under `website/docs/`. Local preview:

```bash
cd website && npm install && npm start
```

## Monorepo packages

| Package | Path |
|---------|------|
| `velmphp/core` | `packages/core` |
| `velmphp/views` | `packages/views` |
| `velmphp/modules` | `packages/modules` |
| `velmphp/console` | `packages/console` |
| `velmphp/web` | `packages/web` |
| `velmphp/ui` | `packages/ui` |
| `velmphp/admin` | `packages/admin` |
| `velmphp/framework` | `packages/framework` |

Common bundled modules (shipped from `packages/modules/modules/`):

- **`base`** — core models (`res.company`, `res.users`, currencies, cron, ACL)
- **`admin`** — panel shell, apps catalog, stored views
- **`file_manager`** — attachments library and file pickers
- **`geo_data`** — geography reference data and import actions
- **`system_audit`** — IT audit trail (`ir.audit.log`, `ir.login.log`, `ir.user.lifecycle`) with CSV export, retention cron, and append-only logs

Bundled Velm modules (`base`, `admin`, …) ship under `packages/modules/modules/`.

## Development

Requires **PHP 8.3+** with extensions **intl** and **pdo_sqlite** (module feature tests use in-memory SQLite). Tests use [Pest](https://pestphp.com/) 4.

```bash
composer install
composer test
composer analyse   # optional
```

CLI without a Laravel app:

```bash
php packages/console/bin/velm module:list --discovered-only
```

### Runnable Velm application (`velmphp/app`)

Manual E2E (admin panel, demo modules, API). Monorepo path: `apps/demo/`:

```bash
cd apps/demo
composer install
composer run setup
composer run dev
```

Sign in at `/velm` with `admin@velm.test` / `password`. Minimal greenfield template: **`apps/app/`** (`velmphp/app` on Packagist). See **[apps/demo/README.md](./apps/demo/README.md)**.

## Status

Early development — module runtime, recordset ORM, partners addon, and Filament arch bridge are landing incrementally. See **[ROADMAP.md](./ROADMAP.md)**.

## License

MIT. See [LICENSE](./LICENSE).
