---
sidebar_position: 2
---

# Installation

Install the skeleton app so you can develop and test Velm modules locally. Model APIs are covered in the [Models](../models/) section.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ (Velm UI assets and this docs site)
- SQLite or the database configured in `apps/skeleton/.env`

## Skeleton app

From the monorepo root:

```bash
cd apps/skeleton
composer install
cp .env.example .env
php artisan key:generate
composer run setup
```

`composer run setup` runs migrations and installs bundled Velm modules (`base`, `admin`, `partners`, …).

### UI assets

Build Tailwind CSS and Flowbite JS for the shell (from monorepo root):

```bash
composer build-ui
```

The skeleton `composer run setup` also runs `velm-build-css`, which builds and publishes assets to `apps/skeleton/public/`.

## Development server

```bash
composer run dev
```

Sign in with the credentials from your `.env` (default seed: `admin@velm.test` / `password`). Velm’s `res.users` uses Laravel’s **`users`** table; override bootstrap credentials with `VELM_ADMIN_EMAIL` and `VELM_ADMIN_PASSWORD` in `.env` or `config/velm.php`.

After login you land on the **apps catalog** (`/velm/apps`). Open **Demos → Projects** to exercise relational fields, or install more modules from the catalog.

See [Platform features](./features) for a full feature list, [Admin panel](./admin-panel) for navigation and branding, and [Views and forms](./views-and-forms) for list/form UX.

## Modules

Velm modules are **not** Composer packages. They are discovered from addon roots configured in `config/velm.php`.

```bash
php artisan list velm                        # all Velm commands
php artisan velm:migrate                     # bootstrap modules (same as composer run setup)
php artisan velm:module:list
php artisan velm:module:install partners       # alias path: velm:migrate --module=partners
php artisan velm:module:sync partners
php artisan velm:module:uninstall partners_ext # remove install state + views/menus (keeps tables)
php artisan velm:db:diff --module=partners
php artisan velm:db:status
php artisan velm:db:autogen inventory --with-views
php artisan velm:make:module my_module       # scaffold under app/modules/
```

See [Module migrations](./migrations) for schema diff, versioned scripts, and hooks. See [Scaffolding modules](./scaffolding) for the full `make:*` workflow.

The skeleton app installs a **`demo_relations`** module (`apps/skeleton/app/modules/demo_relations/`) that seeds sample projects, tasks, and tags — open **Demos** in the Velm shell after `composer run setup`.

Installing a module:

- Resolves manifest `DEPENDS` and loads models in order.
- Creates or alters database tables (additive column diff for extensions).
- Syncs views and menus into `ir.ui.view` / `ir.ui.menu`.

Optional modules such as **`file_manager`** (attachments library) are installed from the apps catalog or `php artisan velm:module:install file_manager`.

## Verifying model inheritance

The test fixture `partners_ext` (under `packages/modules/tests/fixtures/`) extends `res.partner` with a `ref` field. Copy that pattern into your own addon, or run the package tests:

```bash
# from monorepo root
composer test -- packages/modules/tests/Feature/ModelInheritTest.php
```

## Panel configuration (optional)

In `apps/skeleton/.env`:

```env
# Sidebar layout: apps (default) or sidebar
VELM_MENU_LAYOUT=apps

# Header branding when company app_name is empty
VELM_APP_NAME="My ERP"
```

Company-specific branding (logos, colors, application name) is edited under **Settings → Companies**. See [Admin panel](./admin-panel#company-branding).

## What's next

- [Admin panel](./admin-panel) — navigation, apps catalog, branding
- [Views and forms](./views-and-forms) — lists, filters, forms, relational dialogs
- [Models](../models/) — overview, then [Defining models](../models/defining-a-model) or [Extending models](../models/extending-a-model)
