---
sidebar_position: 3
---

# Extending models

Use **`$inherit`** when your module should add columns or behavior to a model you do **not** own — for example `res.partner` from the `partners` module.

You do **not** create a new table. Velm merges your fields into the existing model and runs `ALTER TABLE` to add columns on install.

## Rules

| Rule | Detail |
|------|--------|
| Extend `Model` | `class PartnerPro extends Model` — not the base `Partner` class. |
| Set `$inherit` | `protected static ?string $inherit = 'res.partner';` |
| No `$name` | The extension shares the inherited model name. |
| Declare in manifest | `->models(PartnerPro::class)` on your module. |
| Depend on owner | `->depends('partners')` so the base model loads first. |

## Extension class

```php
<?php

declare(strict_types=1);

namespace Velm\Modules\PartnersPro\Models;

use Velm\Fields\CharField;
use Velm\Models\Model;

final class PartnerPro extends Model
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'ref' => CharField::make()->label('Internal ref'),
        ];
    }
}
```

Only declare **new** fields. Existing fields (`name`, `country_id`, …) come from the base model and earlier extensions.

## Manifest

```php
return Manifest::make('partners_pro')
    ->version(0, 1, 0)
    ->depends('partners')
    ->models(\Velm\Modules\PartnersPro\Models\PartnerPro::class)
    ->summary('Extra partner fields.');
```

## Install

```bash
php artisan velm:module:install partners_pro
```

Velm loads `partners` (registers `Partner`), then `partners_pro` (`registerExtension` merges `ref` into `res.partner`), then adds column `ref` to `res_partner` if missing.

## Read and write

```php
$partner = $env->model('res.partner')->create([
    'name' => 'Acme Corp',
    'ref' => 'ACME-001',
]);

expect($partner->read()[0]['ref'])->toBe('ACME-001');
```

`env->model('res.partner')` uses the **effective** class (your extension when it is the last one registered). Field definitions are merged in the registry, so all layers' columns are valid on create/write.

## Registry

```text
extension chain for res.partner:
  [0] Velm\Modules\Partners\Models\Partner      ← base (table owner)
  [1] Velm\Modules\PartnersPro\Models\PartnerPro
```

- `registry->baseModelClass('res.partner')` → `Partner`
- `registry->modelClass('res.partner')` → last extension (`PartnerPro`)
- `registry->fieldSet('res.partner')` → merged fields from all layers

## Field name collisions

If two extensions define the same field name, **the later module in load order wins** for the merged `Field` descriptor. Prefer unique field names per addon.

## Common mistakes

**Extending `Partner` instead of `Model`**

Velm expects `extends Model` and resolves the chain via the registry. Subclassing `Partner` couples you to load order and is unnecessary.

**Missing `depends('partners')`**

Registration fails with *model not found in registry* if the base module has not loaded yet.

**Setting `$name` on the extension**

Extensions must not set `$name`. Only `$inherit`.

For method hooks and chaining, see [Method overrides and `super()`](./method-overrides-and-super).
