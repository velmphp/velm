---
sidebar_position: 2
---

# Defining models

A **base model** owns a table. You set `$name`, optionally `$table`, implement `defineFields()`, and list the class in the module manifest.

## Model class

Bundled modules live under `packages/modules/modules/{module}/models/`. In your own addon, mirror the same layout.

```php
<?php

declare(strict_types=1);

namespace Velm\Modules\Widgets\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

class Widget extends Model
{
    protected static ?string $name = 'res.widget';

    protected static ?string $table = 'res_widget';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required(),
            'code' => CharField::make()->maxLength(16),
        ];
    }
}
```

Velm automatically adds `id` and `display_name` (readonly) on base models. You do not declare them in `defineFields()`.

## Manifest

```php
<?php

use Velm\Modules\Manifest;
use Velm\Modules\Widgets\Models\Widget;

return Manifest::make('widgets')
    ->version(0, 1, 0)
    ->depends('base')
    ->models(Widget::class)
    ->summary('Widgets.');
```

Models are loaded in **dependency order**. `widgets` must depend on any module whose models you reference in `Many2oneField::comodel()`.

## Install

```bash
php artisan velm:module:install widgets
```

On first install, Velm creates `res_widget` with columns for each field. Later upgrades use **additive** column diff (new fields only).

## Using the model

```php
$env = app(Velm\VelmManager::class)->environment();

$widget = $env->model('res.widget')->create([
    'name' => 'Demo widget',
    'code' => 'DEMO',
]);

$rows = $env->model('res.widget')->search([], limit: 10);
```

## Field types

| Class | Purpose |
|-------|---------|
| `CharField` | Short text |
| `TextField` | Long text |
| `IntegerField` | Integers |
| `BooleanField` | True/false |
| `Many2oneField` | Foreign key (`comodel('res.country')`) |

Use fluent setters: `CharField::make()->required()->maxLength(2)`.

## Checklist

- [ ] `$name` is unique across all installed modules.
- [ ] `$table` matches your SQL naming convention (usually underscores).
- [ ] Model class is listed in `__velm__.php` `->models(...)`.
- [ ] Module `depends(...)` includes every module you rely on.

When your module adds to a model owned elsewhere, continue with [Extending models](./extending-a-model).
