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

Velm automatically adds on every **base** model (you do not declare these in `defineFields()`):

| Field | Purpose |
|-------|---------|
| `id` | Primary key (readonly) |
| `display_name` | Label for relations and UI (readonly) |
| `created_at` | Set on `create()` (readonly `DatetimeField`) |
| `updated_at` | Set on `create()` and `write()` (readonly `DatetimeField`) |

Columns are created on install/sync via schema diff. To disable for a model (e.g. a pure SQL view), set `protected static bool $timestamps = false;`. To use custom column names, declare `created_at` / `updated_at` yourself in `defineFields()` — Velm will not add duplicates.

Tables that already have Laravel-style timestamps (such as `users`) use the same field names; list/form scaffolds skip timestamp fields by default.

Timestamps are stored in **UTC** in the database. The admin panel and API bound to a company show and accept datetimes in that company’s **Timezone** field on `res.company` (see [Platform features — UTC and timezone](../guides/features#utc-storage-and-company-timezone)).

For Laravel-owned columns on a Velm table (e.g. `users.password`), implement `schemaExternalColumns()` so schema diff does not report false sync pending.

## Manifest

Place the model under `addons/widgets/models/Widget.php` (or `models/widget.php` with class `Widget`). Velm **auto-discovers** every `Model` subclass in `models/` — you do not need `->models(...)` in the manifest for conventional layouts.

```php
<?php

use Velm\Modules\Manifest;

return Manifest::make('widgets')
    ->version(0, 1, 0)
    ->depends('base')
    ->summary('Widgets.');
```

Use `->models(ExtraModel::class)` only when a class lives **outside** `models/` (shared support namespaces, legacy paths).

Models are loaded in **module dependency order**. `widgets` must depend on any module whose models you reference in `Many2oneField::comodel()`.

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
| `DatetimeField` | Timestamp (`created_at` / `updated_at` are added automatically) |
| `Many2oneField` | Foreign key (`comodel('res.country')`) |

For **One2many** and **Many2many** (inverse FK, junction tables, read/write semantics), see [Relational fields](./relational-fields).

Use fluent setters: `CharField::make()->required()->maxLength(2)`.

## Computed fields

Declare a field with `->compute('methodName')` and list dependencies with `->depends('field', 'other_field')`. The compute method receives a `Recordset` and returns an **id → value** map:

```php
'headline' => CharField::make()
    ->compute('computeHeadline')
    ->depends('title', 'subtitle'),
```

```php
public function computeHeadline(Recordset $records): array
{
    $values = [];
    foreach ($records->read(['title', 'subtitle']) as $row) {
        $title = (string) ($row['title'] ?? '');
        $subtitle = (string) ($row['subtitle'] ?? '');
        $values[(int) $row['id']] = trim($subtitle !== '' ? "{$title}: {$subtitle}" : $title);
    }
    return $values;
}
```

| Mode | API | Behavior |
|------|-----|----------|
| **Unstored** | `->compute(...)->depends(...)` only | Recomputed on every read |
| **Stored** | Add `->stored()` | Column created on install; recomputed when dependencies change on write |

Computed fields appear in list and form views like normal columns. Cycles in the dependency graph are rejected at registry build time.

## Abstract mixins

Compose shared behavior with **`$mixins`** (Odoo-style abstract models). Example: enable mail chatter on a concrete model:

```php
protected static array $mixins = ['mail.thread'];
```

Requires the **`mail`** module. The shorthand `protected static bool $mailThread = true` still works for backward compatibility. See [Mail thread & chatter](../guides/views-and-forms#mail-thread--chatter).

## Checklist

- [ ] `$name` is unique across all installed modules.
- [ ] `$table` matches your SQL naming convention (usually underscores).
- [ ] Model class lives in `models/` (auto-discovered) or is listed in `->models(...)` when outside that folder.
- [ ] Module `depends(...)` includes every module you rely on.

When your module adds to a model owned elsewhere, continue with [Extending models](./extending-a-model).
