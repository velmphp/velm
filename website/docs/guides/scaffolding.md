---
sidebar_position: 3
---

# Scaffolding modules

Velm ships Artisan generators that mirror PyVelm's `make:*` workflow. Run them from a Laravel app (for example `apps/skeleton`) after `composer install`.

All commands are listed with `php artisan list velm`.

## End-to-end workflow

Scaffold a new addon under `addons/` (or another path in `config/velm.php`):

```bash
cd apps/skeleton

php artisan velm:make:module inventory
php artisan velm:make:model product --module=inventory
php artisan velm:make:view product --module=inventory
php artisan velm:make:menu --view=product.list --module=inventory
php artisan velm:db:autogen inventory --with-views
php artisan velm:migrate --module=inventory
php artisan velm:module:sync --module=inventory
```

For app addons, add a PSR-4 mapping in `composer.json` before migrating:

```json
"autoload": {
  "psr-4": {
    "Addons\\Inventory\\Models\\": "addons/inventory/models/"
  }
}
```

Then run `composer dump-autoload`.

## `velm:make:module`

Creates a module directory with manifest, `models/`, and `migrations/`:

```bash
php artisan velm:make:module inventory
php artisan velm:make:module inventory --depends=base,partners
php artisan velm:make:module inventory --path=/path/to/addon/root
```

## `velm:make:model`

Creates a model class and registers it in `__velm__.php`:

```bash
php artisan velm:make:model product --module=inventory
php artisan velm:make:model inventory.product --module=inventory
php artisan velm:make:model product --module=inventory --force
```

Short names are scoped to the module (`product` → `inventory.product`). Bundled modules use the `Velm\Modules\…` namespace; app addons use `Addons\…`.

## `velm:make:view`

Creates `views/{stem}.php` with list and form views. By default, columns and form sections are built from registered model fields:

```bash
php artisan velm:make:view product --module=inventory
php artisan velm:make:view res.partner --module=partners
php artisan velm:make:view product --module=inventory --minimal
php artisan velm:make:view product --module=inventory --force
```

Use `--minimal` when the model is not registered yet (stub with a `name` field only).

## `velm:make:menu`

Creates or extends `views/menu.php` with sidebar entries that point at a list view:

```bash
php artisan velm:make:menu --view=product.list --module=inventory
php artisan velm:make:menu --view=product.list --module=inventory --group=main --group-label="Inventory"
php artisan velm:make:menu --view=line.list --module=inventory --append
```

| Option | Default | Purpose |
|--------|---------|---------|
| `--view` | *(required)* | List view id (e.g. `product.list`) |
| `--module` | cwd / inferred | Owning module |
| `--group` | `main` | Sidebar group id |
| `--group-label` | module title | Group label |
| `--item` | `{group}.{view-stem}` | Menu item id |
| `--append` | off | Add item to existing `views/menu.php` |
| `--force` | off | Replace `views/menu.php` |

After scaffolding menus, sync the module so entries appear in `ir.ui.menu`:

```bash
php artisan velm:module:sync --module=inventory
```

## `velm:db:autogen --with-views`

Writes a versioned migration from the schema diff and bumps the manifest version. With `--with-views`, it also scaffolds list+form views for models whose tables are touched by the diff (skipping models that already have views):

```bash
php artisan velm:db:autogen inventory --with-views
php artisan velm:db:autogen inventory --dry-run
php artisan velm:db:autogen inventory --version=0.2.0
```

This is useful after `make:model` when you want migration + views in one step before `velm:migrate`. For the two-layer migration model, hooks, and versioned scripts, see [Module migrations](./migrations).

## Related commands

| Command | Purpose |
|---------|---------|
| `velm:db:diff --module=` | Preview schema changes |
| `velm:db:status` | Installed vs manifest versions |
| `velm:migrate --module=` | Install or upgrade a module |
| `velm:module:sync --module=` | Reload DATA views/menus without version bump |

See [Installation](./installation) for skeleton setup and [Defining models](../models/defining-a-model) for hand-written model APIs.
