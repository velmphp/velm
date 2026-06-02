# Velm skeleton app

Runnable Laravel host for the Velm monorepo — Filament panel, module install, and PyVelm-style shell navigation.

## Quick start (monorepo)

From the repository root:

```bash
composer install
cd apps/skeleton
composer install
composer run setup
composer run dev
```

Open [http://127.0.0.1:8000/velm](http://127.0.0.1:8000/velm) and sign in:

| Field | Value |
|-------|--------|
| Email | `admin@velm.test` |
| Password | `password` |

Install more modules at `/velm/apps` or:

```bash
php artisan velm:module:install <name>
php artisan velm:module:sync <name>
```

## What `composer run setup` does

1. `php artisan migrate` — Laravel tables (`users`, `sessions`, …)
2. `php artisan velm:migrate` — bootstrap modules (`base`, `admin`)
3. `php artisan velm:module:install partners` — demo CRM data model + views/menus
4. `php artisan db:seed` — Filament admin user

## Configuration

`config/velm.php` resolves addon paths for monorepo dev (`../../packages/modules/modules`) and Packagist installs (`vendor/velmphp/modules/modules`). Drop custom modules under `addons/`.

```bash
# Optional: nested sidebar instead of apps rail + top bar
VELM_MENU_LAYOUT=sidebar
```

## HTTP surfaces

| URL | Purpose |
|-----|---------|
| `/velm` | Filament panel (arch-driven list/form pages) |
| `/velm/apps` | Module catalog |
| `/api/views/{module}/{name}` | Resolved view arch JSON |

## Artisan commands

| Command | Description |
|---------|-------------|
| `php artisan velm:migrate` | Install bootstrap modules |
| `php artisan velm:module:install {name}` | Install a module + dependencies |
| `php artisan velm:module:sync {name}` | Reload module DATA files |
| `php artisan velm:module:list` | Discovered modules and state |

## Standalone app (future)

When `velmphp/skeleton` ships on Packagist:

```bash
composer create-project velmphp/skeleton my-erp
cd my-erp
composer run setup
composer run dev
```

## Monorepo tests

Package tests still run from the repo root: `composer test` (Testbench). This app is for manual E2E and integration smoke checks.
