---
sidebar_position: 2.5
---

# App addons

Velm modules are **directories on disk**, not Composer packages. Your business logic lives under `addons/` in your Velm application (or any path listed in `config/velm.php`).

## Install the application first

Greenfield install is documented step-by-step in **[Installation](./installation)**:

```bash
composer create-project velmphp/app my_app
cd my_app
composer run setup
composer run dev
```

That gives you `vendor/velmphp/modules/modules/` (bundled modules) and an empty `addons/` directory for your code.

Velm is **not** `nWidart/laravel-modules`. Addons are **not** separate Composer packages in the default workflow. Install state is tracked in `ir.module`, not `composer.json`.

### Add Velm to an existing Laravel app (advanced)

```bash
composer require velmphp/framework
php artisan vendor:publish --tag=velm-config
mkdir -p addons
php artisan migrate
php artisan velm:migrate
```

See [Installation](./installation) for database, assets, and scheduler requirements.

## Autoloading — no `composer.json` per addon

PHP classes in addons use the namespace convention:

```
addons/inventory/
  __velm__.php
  models/Product.php          → Addons\Inventory\Models\Product
  InventoryInstallHooks.php   → Addons\Inventory\InventoryInstallHooks
  Dashboard/SummaryWidget.php → Addons\Inventory\Dashboard\SummaryWidget
```

Velm registers a runtime autoloader for the `Addons\` prefix. It maps `Addons\{StudlyModule}\…` to `addons/{snake_module}/…` (with `Models` → `models/`). **You do not add PSR-4 entries per module** and you do not run `composer dump-autoload` after scaffolding.

Bundled modules shipped in `velmphp/modules` use `Velm\Modules\{StudlyModule}\…` with the same on-disk layout.

Extra addon roots (optional):

```env
# Comma-separated directories in addition to addons/
VELM_ADDON_PATHS=/opt/erp-modules
```

Or in `config/velm.php`:

```php
'addon_autoload_paths' => [
    base_path('addons'),
    '/opt/erp-modules',
],
```

## Model registration

Models under `models/` are **discovered automatically** on install — no `->models(...)` in `__velm__.php` required.

| Location | Registration |
|----------|----------------|
| `addons/inventory/models/Product.php` | Auto (conventional) |
| `src/Support/LegacyWidget.php` | Explicit `->models(LegacyWidget::class)` |

File `models/product.php` maps to class `Addons\Inventory\Models\Product` (same rules as `velm:make:model`).

## Authoring workflow

```bash
php artisan velm:make:module inventory
php artisan velm:make:model product --module=inventory
php artisan velm:make:view product --module=inventory
php artisan velm:make:menu --view=product.list --module=inventory
php artisan velm:migrate --module=inventory
```

See [Scaffolding](./scaffolding) for generator details and [Module migrations](./migrations) for schema upgrades.

## Production notes

- **Deploy** addon folders with your app (git submodule, rsync, CI artifact) — same as copying an Odoo module.
- **Install / upgrade** on the server: `php artisan velm:migrate` or `php artisan velm:module:sync {name}` after pulling code.
- **Composer** is only needed when the Velm framework version changes, not when you add PHP files under `addons/`.
- **Cron**: schedule `php artisan velm:cron:run` (see production ops guide when published).

Optional future path: Composer-distributable addons via `velmphp/composer-plugin` (`type: velm-module`) — not required for v1.
