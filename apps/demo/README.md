# Velm demo application (`velmphp/velm-demo`)

Reference Laravel host for **monorepo development and E2E** — same base as `apps/app/` plus demo addons and extended setup. **Not published on Packagist.**

For greenfield projects use `composer create-project velmphp/app` (`apps/app/`).

## Quick start

From the repository root:

```bash
composer install
cd apps/demo
composer install
composer run setup
composer run dev
```

Open [http://127.0.0.1:8000/velm](http://127.0.0.1:8000/velm) — sign in with `admin@velm.test` / `password`.

Path repositories to `../../packages/*` are committed in `composer.json` (monorepo-only).

## What `composer run setup` does

1. Publish Velm shell CSS/JS
2. Laravel + Velm bootstrap migrations (`base`, `admin`)
3. Install reference modules: `partners`, `partners_ext`, `workflow`, `change_management`, `file_manager`, `demo_relations`
4. Seed demo Velm records (`velm:seed` — countries, partners, and other module fixtures)
5. Seed admin user

Demo addon details: [addons/README.md](./addons/README.md).

## UI rebuild (monorepo)

After editing `packages/ui/resources/css/velm.src.css`:

```bash
composer run velm-rebuild-ui
```

## Resetting the Velm schema

```bash
php artisan velm:migrate:fresh --yes --module=partners --module=partners_ext --module=workflow --module=change_management --module=file_manager --module=demo_relations
php artisan velm:seed
```

## Divergence from `apps/app`

| | `apps/app` | `apps/demo` (this tree) |
|---|---|---|
| Packagist | `velmphp/app` | Not published |
| `addons/` | Empty | Demo modules |
| `velm-setup` | Bootstrap only | + reference/demo modules |
| Path repos | `composer.local.json` | In `composer.json` |

When changing shared Laravel config or migrations, update **`apps/app`** first, then sync into `apps/demo` if needed.
