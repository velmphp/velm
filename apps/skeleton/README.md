# Velm skeleton app

Minimal Laravel host for local development and manual E2E checks of the Velm panel.

## Monorepo development

From the repository root:

```bash
composer install
composer test
```

Filament integration tests boot a Testbench app with the Velm panel at path `/velm`, installing bundled `base` and `partners` modules automatically.

Configure addon roots in `config/velm.php`:

```php
'addon_paths' => [
    base_path('vendor/velmphp/modules/modules'),
],
'bootstrap_modules' => ['base', 'admin'],
```

## Standalone app (future)

When `velmphp/skeleton` ships on Packagist:

```bash
composer create-project velmphp/skeleton my-erp
cd my-erp
php artisan migrate
php artisan serve
```

Visit `/velm` for the Velm Filament panel and `/velm/apps` for the module catalog.
