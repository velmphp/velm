---
sidebar_position: 5
---

# Stacking extensions

Several modules can extend the same model. Each registers an extension class; Velm appends it to the **MRO chain** in module load order.

This is the most involved model-inheritance topic: multiple addons, explicit `depends`, and chained `super()` calls.

## Scenario

| Module | Adds |
|--------|------|
| `partners` | Base `res.partner` (`name`, `country_id`, …) |
| `partners_ext` | Field `ref`, display name suffix `(ref)` |
| `partners_tags` | Field `tag`, display name suffix `#tag` |

All three use `extends Model` and `$inherit = 'res.partner'`. None subclasses another extension.

## MRO after install

Assuming manifest dependencies order loads `partners` → `partners_ext` → `partners_tags`:

```text
[0] Partner
[1] PartnerExtension
[2] PartnerTagsExtension   ← effective model class
```

- `registry->modelClass('res.partner')` → `PartnerTagsExtension`
- `registry->extensionsFor('res.partner')` → `[PartnerExtension, PartnerTagsExtension]`

## Chaining `super()` through the stack

```php
// PartnerTagsExtension
public static function displayNameFor(array $values): string
{
    $base = static::super($values);  // → PartnerExtension::displayNameFor
    $tag = (string) ($values['tag'] ?? '');
    return $tag === '' ? $base : $base.' #'.$tag;
}
```

```php
// PartnerExtension
public static function displayNameFor(array $values): string
{
    $base = static::super($values);  // → Partner::displayNameFor
    $ref = (string) ($values['ref'] ?? '');
    return $ref === '' ? $base : $base.' ('.$ref.')';
}
```

Record with `name`, `ref`, and `tag`:

```text
Velm Labs (VL-001) #gold
```

## Load order with `depends`

Load order follows **topological sort** of manifest `depends`. To run after another extension module:

```php
return Manifest::make('partners_tags')
    ->depends('partners', 'partners_ext')
    ->models(PartnerTagsExtension::class);
```

Without that dependency, discovery order and dependency ties decide placement. **Declare dependencies explicitly** when one extension's logic assumes another's fields exist.

## Schema

Each extension's fields are merged into `fieldSet('res.partner')`. Install runs `ALTER TABLE` on the **base** table (`res_partner`) once per new column.

Install order does not create multiple tables — only one table per model name.

## Independent vs dependent extensions

| Style | Manifest | Result |
|-------|----------|--------|
| Independent | `depends('partners')` only | Loaded when sorted; may run before/after other extensions unless ties are broken by discovery |
| Dependent | `depends('partners', 'partners_ext')` | Guaranteed after `partners_ext` |

Both are valid. Use **depends** to express hard ordering requirements.

## Tests

Fixture modules under `packages/modules/tests/fixtures/`:

- `partners_ext`
- `partners_ext_independent`
- `partners_ext_chained`

```bash
composer test -- packages/modules/tests/Feature/ModelInheritTest.php
composer test -- packages/core/tests/RegistryModelInheritTest.php
```

## Models vs views

| | Model `$inherit` | View `VIEW_INHERITS` |
|--|------------------|----------------------|
| Changes | Table columns, PHP methods | JSON arch (list/form fields) |
| Mechanism | Registry MRO + `super()` | Declarative ops (`op_after`, …) |
| Docs | This section | (coming) |

You often use **both** on the same business object: model extension for `ref`, view inheritance to show `ref` on the partner form.

## Checklist

- [ ] Each extension `extends Model` and sets `$inherit`.
- [ ] Manifest `depends(...)` lists every module that must load first.
- [ ] Overrides use `static::super($args)` to chain behavior.
- [ ] Field names are unique or intentionally overridden with knowledge of load order.
- [ ] Re-run `velm:module:install` / `sync` after adding fields in development.
