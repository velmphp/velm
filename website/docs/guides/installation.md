---
sidebar_position: 2
---

# Installation

Install the skeleton app so you can develop and test Velm modules locally. Model APIs are covered in the [Models](../models/) section.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ (Filament assets and this docs site)
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

## Development server

```bash
composer run dev
```

Sign in with the credentials from your `.env` (default seed: `admin@velm.test` / `password`).

## Modules

Velm modules are **not** Composer packages. They are discovered from addon roots configured in `config/velm.php`.

```bash
php artisan list velm                        # all Velm commands
php artisan velm:migrate                     # bootstrap modules (same as composer run setup)
php artisan velm:module:list
php artisan velm:module:install partners       # alias path: velm:migrate --module=partners
php artisan velm:module:sync partners
php artisan velm:db:diff --module=partners
php artisan velm:db:status
php artisan velm:db:autogen inventory --with-views
php artisan velm:make:module my_module       # scaffold under addons/
```

See [Scaffolding modules](./scaffolding) for the full `make:*` workflow (`make:model`, `make:view`, `make:menu`).

Installing a module:

- Resolves manifest `DEPENDS` and loads models in order.
- Creates or alters database tables (additive column diff for extensions).
- Syncs views and menus into `ir.ui.view` / `ir.ui.menu`.

## Verifying model inheritance

The test fixture `partners_ext` (under `packages/modules/tests/fixtures/`) extends `res.partner` with a `ref` field. Copy that pattern into your own addon, or run the package tests:

```bash
# from monorepo root
composer test -- packages/modules/tests/Feature/ModelInheritTest.php
```

## What's next

Read [Models](../models/) for an overview, then [Defining models](../models/defining-a-model) if you are adding a new table, or [Extending models](../models/extending-a-model) if you are building on another module's model.
