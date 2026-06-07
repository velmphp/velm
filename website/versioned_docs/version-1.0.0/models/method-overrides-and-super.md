---
sidebar_position: 4
---

# Method overrides and `super()`

Fields are not the only extension point. Extensions can override **static hooks** (for example `displayNameFor()`) and **instance methods** called on recordsets (for example `badge()`), chaining with **`static::super()`** in both cases.

This mirrors PyVelm's `super()` in `_inherit` stacks, but uses Velm's [extension order (MRO)](index.md#extension-order-mro) instead of PHP subclassing between addons.

**MRO** (*method resolution order*) is the ordered list of base + extension classes for a model. `static::super()` calls the **previous** class in that list — see [Extension order (MRO)](index.md#extension-order-mro) on the Models overview for a full explanation.

## When to override

| Method | Typical use |
|--------|-------------|
| `displayNameFor(array $values)` (static) | Label shown in lists, many2one dropdowns, APIs |
| `badge(Recordset $records)` (instance) | Business logic invoked as `$partner->badge()` |
| Computed field methods | `->compute('computeScore')->depends('title')` — see [Defining models — Computed fields](./defining-a-model#computed-fields) |

Override sparingly. Prefer view-layer formatting when the change is display-only.

## Override with `super()`

```php
final class PartnerPro extends Model
{
    protected static ?string $inherit = 'res.partner';

    public static function defineFields(): array
    {
        return [
            'ref' => CharField::make()->label('Internal ref'),
        ];
    }

    public static function displayNameFor(array $values): string
    {
        $base = static::super($values);
        $ref = $values['ref'] ?? '';

        if ($ref !== '') {
            return $base.' ('.$ref.')';
        }

        return $base;
    }
}
```

### How `super()` works

1. Velm inspects the **caller method name** (e.g. `displayNameFor`).
2. The registry walks the [MRO chain](index.md#extension-order-mro) **backward** from your class to find the nearest parent that implements the same method (static or instance, matching the call).
3. That class's method is invoked with your arguments.

Middle extensions that do not override a method are skipped — you do not need a stub in every layer.

You do **not** pass `__FUNCTION__` anymore. This form is supported for compatibility but optional:

```php
$base = static::super(__FUNCTION__, $values); // legacy, still works
```

### Requirements

- A registry must be active (normal for `env->model(...)->read()`, HTTP APIs, and tests inside `Registry::using()`).
- Your class must be registered as an extension with `$inherit` set on **this** class (reflection checks the declaring class).

## Instance methods on recordsets

Define **public instance methods** on the model class. Call them on a recordset; Velm dispatches to the nearest implementor in the MRO (usually the effective class).

```php
// partners/models/Partner.php
public function badge(Recordset $records): string
{
    $records->ensureOne();

    return (string) ($records->read()[0]['name'] ?? '');
}

// partners_ext_chained extension
public function badge(Recordset $records): string
{
    $base = static::super($records);
    $records->ensureOne();
    $ref = (string) ($records->read()[0]['ref'] ?? '');

    return $ref === '' ? $base : $base.' · '.$ref;
}
```

```php
$partner = $env->model('res.partner')->create(['name' => 'Acme', 'ref' => 'ACME-001']);
$partner->badge(); // → "Acme · ACME-001"
```

Rules:

- First parameter is always the **recordset** (`$records`). Velm passes it automatically.
- Chain with **`static::super($records, ...)`** — not `$this->super()` (PHP allows only one `super` name; use the static form from instance methods too).
- Use `$records->ensureOne()` when the logic applies to a single row.
- Methods must be **public** and declared on a class that `extends Model` (not on the abstract `Model` base itself).

## Base behavior

The default `Model::displayNameFor()` uses `$recName` (usually `name`), then `id`, then the model name:

```php
// Partner with name "Acme" → display_name "Acme"
// PartnerPro with ref → "Acme (ACME-001)"
```

Calling `Country::displayNameFor()` directly on the base class does **not** re-dispatch to extensions — that would cause infinite recursion. Runtime paths use the **effective** extension class from the registry.

## Where overrides run

| Path | Behavior |
|------|----------|
| `$recordset->read()` | Computes `display_name` via effective model class |
| `GET /api/records` | Serializer uses same hook |
| Direct `PartnerPro::displayNameFor(...)` in tests | Works inside `Registry::using()` |

## Errors

**No parent in MRO**

Calling `super()` on the base model throws: *has no parent in the model MRO*.

**No implementor in the chain**

If no class below yours in the MRO implements the method, `super()` throws: *has no parent in the model MRO*.

## Why not `parent::`?

PHP `parent::` requires your extension to `extend Partner`, then `extend PartnerOtherExtension`, and so on. Addon authors would need to know which module extended the model last.

Velm instead:

- Always `extend Model`
- Always `$inherit = 'res.partner'`
- Chain with `static::super($args)`

Module **install order** (topological sort of `depends`) defines the [MRO](index.md#extension-order-mro).

For several independent addons on one model, see [Stacking extensions](./stacking-extensions).

For the full design rationale (MRO dispatch, behavior objects, static hook resolution), see [RFC 0001: Model record methods](https://github.com/velmphp/velm/blob/main/docs/rfcs/0001-model-record-methods.md) in the monorepo.
