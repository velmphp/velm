# Velm application (`velmphp/app`)

Official Laravel host for a Velm ERP — Livewire panel, `addons/`, module install, and PyVelm-style shell navigation.

In the monorepo this app lives at `apps/skeleton/`; on Packagist it is published as **`velmphp/app`**.

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
php artisan velm:module:uninstall <name>
```

## What `composer run setup` does

1. `composer velm-build-css` — build Tailwind + Flowbite (`velm.css`, `flowbite.min.js`) and publish to `public/css/velm/` and `public/js/velm/`
2. `php artisan migrate` — Laravel tables (`users`, `sessions`, …)
3. `php artisan velm:migrate` — bootstrap modules (`base`, `admin`)
4. `php artisan velm:module:install partners` — demo CRM data model + views/menus
5. `php artisan velm:module:install demo_relations` — relational fields demo (M2O / O2M / M2M) under **Demos** in the shell
6. `php artisan db:seed` — admin user

If the UI looks unstyled or colors are wrong, run `composer velm-build-css` (rebuilds `packages/ui` Tailwind and publishes assets). You can also re-run `composer run setup`.

### Resetting the Velm schema

When you want a clean Velm database (for example after changing modules or during local testing) you can drop Velm-owned tables and reinstall bootstrap modules without touching Laravel’s own tables:

```bash
php artisan velm:migrate:fresh       # ask for confirmation
php artisan velm:migrate:fresh --yes # skip the prompt (CI-safe)
```

By default this will:

- Drop Velm model tables and many-to-many relation tables, while keeping `users`, `sessions`, `jobs`, and other core Laravel tables.
- Reinstall the bootstrap modules from `config/velm.php` (usually `base` and `admin`).
- Optionally migrate additional modules when you pass `--module=` flags:

```bash
php artisan velm:migrate:fresh --yes --module=partners --module=demo_relations
```

After running `velm:migrate:fresh` you can sign in with the same Laravel user credentials as before, but all Velm business data (partners, projects, workflow items, etc.) will be empty until you create or seed it again.

## Configuration

`config/velm.php` resolves addon paths for monorepo dev (`../../packages/modules/modules`) and Packagist installs (`vendor/velmphp/modules/modules`). Drop custom modules under `addons/` (see `addons/README.md`).

**No per-addon `composer.json` PSR-4 entries.** Classes under `addons/{module}/` use `Addons\{StudlyModule}\…` and are autoloaded by Velm at runtime.

### Production install (Packagist)

```bash
composer require velmphp/framework
php artisan vendor:publish --tag=velm-config
php artisan migrate && php artisan velm:migrate
```

Bundled modules come from `velmphp/modules`; your business modules stay in `addons/`. Full journey: [App addons](https://github.com/velmphp/velm/blob/main/website/docs/guides/addons.md).

```bash
# Optional: nested sidebar instead of apps rail + top bar
VELM_MENU_LAYOUT=sidebar
```

## HTTP surfaces

| URL | Purpose |
|-----|---------|
| `/velm` | Dashboard (default home) |
| `/velm/apps` | Module catalog (install / sync / uninstall) |
| `/api/views/{module}/{name}` | Resolved view arch JSON |
| `/api/records?model=&domain=&fields=` | List records (GET) |
| `/api/records?model=` | Create (POST JSON body) |
| `/api/records/{id}?model=` | Update (PATCH) or delete (DELETE) |
| `/api/m2o/search?model=&q=` | Many2one combobox search (`id` + `label`) |

## Artisan commands

| Command | Description |
|---------|-------------|
| `php artisan velm:migrate` | Install or upgrade bootstrap modules |
| `php artisan velm:migrate:fresh` | Drop Velm tables, reinstall bootstrap modules (keep Laravel tables) |
| `php artisan velm:migrate --module={name}` | One module + dependencies |
| `php artisan velm:module:install {name}` | Same as `velm:migrate --module={name}` |
| `php artisan velm:module:sync {name}` | Reload module DATA + schema diff (no version bump) |
| `php artisan velm:module:uninstall {name}` | Remove module from DB (views/menus; keeps tables) |
| `php artisan velm:module:list` | Discovered modules and state |
| `php artisan velm:db:diff --module={name}` | Schema drift report |
| `php artisan velm:db:autogen --module={name}` | Scaffold migration + bump VERSION |
| `php artisan velm:db:status` | Installed vs manifest versions |
| `php artisan velm:cron:run` | Run due `ir.cron` jobs once |
| `php artisan velm:seed` | Run manifest seeders for installed modules |
| `php artisan list velm` | Full command list |

## Greenfield install (Packagist)

```bash
composer create-project velmphp/app my-erp
cd my-erp
composer run setup
composer run dev
```

## Monorepo tests

Package tests still run from the repo root: `composer test` (Testbench). This app is for manual E2E and integration smoke checks.
