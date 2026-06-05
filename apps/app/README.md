# Velm application (`velmphp/app`)

Minimal Laravel host for a Velm ERP — Livewire panel, empty `addons/`, and bootstrap setup (`base` + `admin`).

Published on Packagist as **`velmphp/app`**. Monorepo path: `apps/app/`.

## Packagist install

```bash
composer create-project velmphp/app my_app
cd my_app
composer run setup
composer run dev
```

Open [http://127.0.0.1:8000/velm](http://127.0.0.1:8000/velm) and sign in with `admin@velm.test` / `password`.

See the [installation guide](https://velmphp.github.io/velm/docs/guides/installation).

## Monorepo development

From the repository root:

```bash
composer install
cd apps/app
cp composer.local.json.example composer.local.json
composer install
composer run setup
composer run dev
```

`composer.local.json` adds path repositories to `../../packages/*` (gitignored).

For the full reference app with demo addons, use **`apps/demo/`** instead.

## What `composer run setup` does

1. `composer velm-build-css` — publish prebuilt Velm shell assets
2. `php artisan migrate` — Laravel tables
3. `php artisan velm:migrate` — bootstrap modules (`base`, `admin`)
4. `php artisan db:seed` — admin user

Install more modules at `/velm/apps` or `php artisan velm:module:install <name>`.

## Resetting the Velm schema

```bash
php artisan velm:migrate:fresh --yes
php artisan velm:seed
```

Maintainer checklist: [PACKAGIST.md](../../PACKAGIST.md).
