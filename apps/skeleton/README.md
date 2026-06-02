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

1. `php artisan filament:assets` — publish Filament CSS/JS/fonts to `public/` (required for styled login and panel)
2. `php artisan migrate` — Laravel tables (`users`, `sessions`, …)
3. `php artisan velm:migrate` — bootstrap modules (`base`, `admin`)
4. `php artisan velm:module:install partners` — demo CRM data model + views/menus
5. `php artisan db:seed` — Filament admin user

If the sign-in page or Velm shell menu looks unstyled, run `php artisan filament:assets` (or re-run `composer run setup`). After changing shell Blade templates in `packages/filament`, rebuild shell CSS with `npm run build:css` in that package, then publish assets again.

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
| `/api/records?model=&domain=&fields=` | List records (GET) |
| `/api/records?model=` | Create (POST JSON body) |
| `/api/records/{id}?model=` | Update (PATCH) or delete (DELETE) |
| `/api/m2o/search?model=&q=` | Many2one combobox search (`id` + `label`) |

## Artisan commands

| Command | Description |
|---------|-------------|
| `php artisan velm:migrate` | Install or upgrade bootstrap modules |
| `php artisan velm:migrate --module={name}` | One module + dependencies |
| `php artisan velm:module:install {name}` | Same as `velm:migrate --module={name}` |
| `php artisan velm:module:sync {name}` | Reload module DATA + schema diff (no version bump) |
| `php artisan velm:module:list` | Discovered modules and state |
| `php artisan velm:db:diff --module={name}` | Schema drift report |
| `php artisan velm:db:autogen --module={name}` | Scaffold migration + bump VERSION |
| `php artisan velm:db:status` | Installed vs manifest versions |
| `php artisan velm:cron:run` | Run due `ir.cron` jobs once |
| `php artisan list velm` | Full command list |

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
