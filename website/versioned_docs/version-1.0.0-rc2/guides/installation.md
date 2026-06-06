---
sidebar_position: 2
---

# Installation

This guide installs a **new Velm application** from Packagist. You end up with a runnable Laravel app, the Velm admin panel, bundled modules, and an `addons/` directory for your own modules.

For how addons differ from Composer packages, see [App addons](./addons). For authoring modules after install, see [Scaffolding](./scaffolding).

## What `composer create-project` gives you

| Piece | Source | Purpose |
|-------|--------|---------|
| **Application** | `velmphp/app` | Laravel project, `composer run setup`, `addons/` |
| **Framework** | `velmphp/framework` (dependency) | Service provider, panel, Artisan commands |
| **Bundled modules** | `velmphp/modules` (transitive) | `base`, `admin`, `partners`, … under `vendor/velmphp/modules/modules/` |
| **Your modules** | `addons/` in the project | Business logic you write (not Composer packages) |

Velm module install state is stored in the database (`ir.module`), not in `composer.json`.

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|--------|
| PHP | 8.3+ | Extensions: `intl`, `pdo_sqlite` (or your DB driver) |
| Composer | 2.x | |
| Node.js | 20+ | **Optional** — only needed if you rebuild Velm UI from source (monorepo contributors) |

Default database is **SQLite** (`database/database.sqlite`). MySQL/Postgres are supported via `.env` (see [Database](#database) below).

## 1. Create the project

Velm **1.0.0-rc1** is a release candidate — there is no stable `1.0.0` yet. Plain `create-project` defaults to **stable** only and will fail with *Could not find package velmphp/app with stability stable*.

Use either form:

```bash
# Recommended — allow RC resolution
composer create-project velmphp/app my_app -s rc
cd my_app
```

Or pin an exact RC tag:

```bash
composer create-project velmphp/app my_app v1.0.0-rc2 -s rc
cd my_app
```

Replace `my_app` with your project directory name. Composer installs Laravel, `velmphp/framework`, and transitive `velmphp/*` libraries at **`^1.0@dev`** — with `"prefer-stable": true`, tagged RC releases are preferred over `dev-main`.

Marking the GitHub release as **pre-release** is correct for an RC; that flag is for humans on GitHub and does not affect Composer. Packagist reads **git tags** on the mirror repos (`velmphp/app`, `velmphp/framework`, …).

## 2. Run setup

From the project root:

```bash
composer run setup
```

This single command runs the following (in order):

| Step | Command / action | Result |
|------|------------------|--------|
| 1 | Copy `.env.example` → `.env` if missing | App configuration file |
| 2 | `php artisan key:generate` | `APP_KEY` set |
| 3 | `composer velm-build-css` | Publishes prebuilt Velm shell CSS/JS to `public/css/velm/` and `public/js/velm/` |
| 4 | Create `database/database.sqlite` | Empty SQLite file (when `DB_CONNECTION=sqlite`) |
| 5 | `php artisan migrate` | Laravel tables (`users`, `sessions`, `jobs`, …) |
| 6 | `php artisan velm:migrate` | Velm bootstrap modules: **`base`**, **`admin`** |
| 7 | `php artisan db:seed` | Bootstrap admin user for the panel |

Install bundled modules (e.g. `partners`) from `/velm/apps` or `php artisan velm:module:install <name>`. Reference demos live in the monorepo **`apps/demo/`** only.

Step 3 uses prebuilt assets from `velmphp/ui`; npm is not required for a normal install.

### Default panel login

After setup, sign in with:

| Field | Default |
|-------|---------|
| Email | `admin@velm.test` |
| Password | `password` |

Override in `.env` before or after setup:

```env
VELM_ADMIN_EMAIL=you@example.com
VELM_ADMIN_PASSWORD=your-secure-password
```

Velm’s `res.users` uses Laravel’s **`users`** table.

## 3. Start the development server

```bash
composer run dev
```

This runs `php artisan serve`. Open:

| URL | Purpose |
|-----|---------|
| [http://127.0.0.1:8000/velm](http://127.0.0.1:8000/velm) | Velm panel (redirects to dashboard / apps) |
| [http://127.0.0.1:8000/velm/apps](http://127.0.0.1:8000/velm/apps) | Module catalog (install / sync / uninstall) |

Set `APP_URL` in `.env` if you use another host or port.

## 4. Verify the install

1. Sign in at `/velm` with the admin credentials above.
2. Open **Apps** — `base` and `admin` should be installed; demo modules from setup may appear as installed.
3. If `demo_relations` was installed during setup, open **Demos → Projects** to confirm list/form views load.

If the UI is unstyled, re-run:

```bash
composer velm-build-css
```

## Project layout (what matters)

```
my_app/
  .env                    # Laravel + optional VELM_* variables
  addons/                 # Your Velm modules (create with velm:make:module)
  config/velm.php         # Addon paths, bootstrap modules, panel options
  database/               # Laravel migrations + database.sqlite (default)
  public/css/velm/        # Published shell styles (from velm-build-css)
  vendor/velmphp/
    modules/modules/      # Bundled modules (base, admin, partners, …)
    framework/            # Velm Laravel integration
```

You do **not** add per-addon entries to `composer.json` for code under `addons/`. Velm autoloads `Addons\{ModuleName}\…` at runtime.

## Database

### SQLite (default)

No extra configuration. Setup creates `database/database.sqlite` automatically.

### MySQL or PostgreSQL

1. Create an empty database.
2. Set `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_app
DB_USERNAME=...
DB_PASSWORD=...
```

3. Run setup (or, if `.env` was changed after setup):

```bash
php artisan migrate --force
php artisan velm:migrate
```

## Install or remove modules

Bundled modules live under `vendor/velmphp/modules/modules/`. Install by name (snake_case):

```bash
php artisan velm:module:install partners
php artisan velm:module:install file_manager
php artisan velm:module:list
php artisan velm:module:sync partners
php artisan velm:module:uninstall partners_ext
```

The same operations are available in the UI at `/velm/apps`.

Installing a module:

- Resolves `DEPENDS` in `__velm__.php` and installs dependencies first.
- Creates or alters tables (additive column diff).
- Syncs views and menus into `ir.ui.view` / `ir.ui.menu`.

## Your first addon

```bash
php artisan velm:make:module inventory
php artisan velm:make:model product --module=inventory
php artisan velm:make:view product --module=inventory
php artisan velm:migrate --module=inventory
```

Models under `addons/inventory/models/` are discovered automatically. See [Scaffolding](./scaffolding) and [App addons](./addons).

## Reset Velm data (development)

Drop Velm-owned tables and reinstall bootstrap modules. Laravel tables (`users`, `sessions`, …) are kept.

```bash
php artisan velm:migrate:fresh --yes
```

Reinstall specific modules after a fresh reset:

```bash
php artisan velm:migrate:fresh --yes --module=partners --module=demo_relations
```

## Seed module data

If a module manifest declares `SEEDERS`:

```bash
php artisan velm:seed
php artisan velm:seed --module=partners
```

Seeders should be idempotent (safe to run twice).

## Optional panel configuration

In `.env`:

```env
VELM_MENU_LAYOUT=apps          # apps (default) or sidebar
VELM_APP_NAME="My ERP"
VELM_PANEL_PATH=velm             # URL prefix; default velm
```

Company logos, colors, and application name: **Settings → Companies** in the panel. See [Admin panel](./admin-panel#company-branding).

## Production deploy (summary)

On each release:

```bash
composer install --no-dev --optimize-autoloader
composer velm-build-css          # or build in CI and deploy public/css/velm/
php artisan migrate --force
php artisan velm:migrate
php artisan config:cache
php artisan route:cache
```

Schedule Velm cron via Laravel’s scheduler:

```php
Schedule::command('velm:cron:run')->everyMinute();
```

System cron: `* * * * * php /path/to/my_app/artisan schedule:run`

Deploy `addons/` with your application code. After pulling addon changes: `php artisan velm:module:sync <module>`.

A full production guide (attachments disk, DB tuning) is on the [roadmap](https://github.com/velmphp/velm/blob/main/ROADMAP.md).

## Artisan reference

```bash
php artisan list velm
```

Common commands: `velm:migrate`, `velm:module:install`, `velm:module:sync`, `velm:db:diff`, `velm:db:status`, `velm:migrate:fresh`, `velm:seed`, `velm:make:module`.

## Monorepo contributors

If you clone [velmphp/velm](https://github.com/velmphp/velm) instead of using `create-project`:

| Goal | Path | Setup |
|------|------|--------|
| Minimal app (matches Packagist) | `apps/app/` | Copy `composer.local.json.example` → `composer.local.json`, then `composer install` |
| Full reference + demo modules | `apps/demo/` | `composer install` (path repos committed) |

```bash
# Minimal — same as create-project
cd apps/app
cp composer.local.json.example composer.local.json
composer install && composer run setup

# Reference demos (partners, workflow, demo_relations, …)
cd apps/demo
composer install && composer run setup
```

## What's next

- [App addons](./addons) — autoloading, module layout, Composer vs on-disk
- [Scaffolding](./scaffolding) — `make:module`, `make:model`, `make:view`
- [Admin panel](./admin-panel) — navigation, apps catalog, branding
- [Models](../models/) — fields, recordsets, inheritance
