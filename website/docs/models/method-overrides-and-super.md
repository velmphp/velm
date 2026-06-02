---
sidebar_position: 4
---

# Method overrides and `super()`

Fields are not the only extension point. An extension class can **override static methods** on the model — for example `displayNameFor()` — and chain to the previous layer with **`static::super()`**.

This mirrors PyVelm's `super()` in `_inherit` stacks, but uses an explicit registry MRO instead of Python's class hierarchy.

## When to override

| Method | Typical use |
|--------|-------------|
| `displayNameFor(array $values)` | Label shown in lists, many2one dropdowns, APIs |
| (future) computed hooks | Same pattern as PyVelm `@depends` |

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
2. The active registry returns the **previous class** in the MRO chain.
3. That class's method is invoked with your arguments.

You do **not** pass `__FUNCTION__` anymore. This form is supported for compatibility but optional:

```php
$base = static::super(__FUNCTION__, $values); // legacy, still works
```

### Requirements

- A registry must be active (normal for `env->model(...)->read()`, HTTP APIs, and tests inside `Registry::using()`).
- Your class must be registered as an extension with `$inherit` set on **this** class (reflection checks the declaring class).

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

**Missing method on parent**

If the parent class does not implement the method, PHP raises a standard error — same as a bad `parent::` call.

## Why not `parent::`?

PHP `parent::` requires your extension to `extend Partner`, then `extend PartnerOtherExtension`, and so on. Addon authors would need to know which module extended the model last.

Velm instead:

- Always `extend Model`
- Always `$inherit = 'res.partner'`
- Chain with `static::super($args)`

Module **install order** (topological sort of `depends`) defines the MRO.

For several independent addons on one model, see [Stacking extensions](./stacking-extensions).
