# Velm

<p align="center">
  <a href="https://github.com/velmphp/velm/actions/workflows/ci.yml"><img src="https://github.com/velmphp/velm/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://www.php.net/releases/8.3.php"><img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/License-LGPL--3.0--or--later-FCA326?style=flat-square&logo=gnu&logoColor=white" alt="License"></a>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-5-F59E0B?style=flat-square" alt="Filament 5"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4-FB70A9?style=flat-square" alt="Livewire 4"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-4-6ADE80?style=flat-square" alt="Pest 4"></a>
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

Manual E2E (admin panel, module install, API). Monorepo path: `apps/skeleton/`:

```bash
cd apps/skeleton
composer install
composer run setup
composer run dev
```

Sign in at `/velm` with `admin@velm.test` / `password`. See **[apps/skeleton/README.md](./apps/skeleton/README.md)**.

## Status

Early development — module runtime, recordset ORM, partners addon, and Filament arch bridge are landing incrementally. See **[ROADMAP.md](./ROADMAP.md)**.

## License

LGPL-3.0-or-later. See [LICENSE](./LICENSE).
