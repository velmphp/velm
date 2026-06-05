# Your Velm modules

App-specific Velm modules live here. Velm autoloads `Addons\{StudlyModule}\…` from this directory — you do **not** register namespaces in `composer.json`.

Scaffold a new module:

```bash
php artisan velm:make:module my_module
php artisan velm:make:model item --module=my_module
php artisan velm:migrate --module=my_module
php artisan velm:module:install my_module
```

Install bundled modules from `vendor/velmphp/modules/modules/` via the panel at `/velm/apps` or:

```bash
php artisan velm:module:install partners
```

For reference demos (relational fields, view inheritance, workflows), see the monorepo **`apps/demo/`** application.
