# RFC 0001: Model record methods and MRO dispatch

| Field | Value |
|-------|-------|
| **Status** | Implemented on `feature/acl-record-rules` (awaiting merge) |
| **Authors** | Velm contributors |
| **Created** | 2026-06-02 |

## Summary

Velm extensions already merge **fields** and support **static hooks** (`displayNameFor`, …) via a registry **MRO** and `static::super()`. This RFC adds **instance methods** callable on recordsets (`$partner->badge()`), unifies **`super()`** for static and instance parents, and fixes **static hook dispatch** so `read()` and the HTTP API use the nearest MRO implementor—not only the effective class’s inherited `Model` default.

## Motivation

### Problem

1. **No business methods on recordsets** — Authors could only extend models with fields and static hooks. Odoo/PyVelm-style actions (`confirm`, `send_reminder`, …) had no first-class story in PHP Velm.
2. **`super()` assumed an immediate parent** — Middle extensions that did not override a method broke chains (e.g. `badge()` on a top extension whose MRO parent was another extension without `badge()`).
3. **Static hooks ignored lower MRO layers** — `read()` called `effectiveClass::displayNameFor()`. If the effective class did not declare the hook, PHP used `Model::displayNameFor`, skipping `PartnerExtension::displayNameFor` below it in the chain.

### Non-goals (this RFC)

- `$record->field` attribute access (record wrapper objects).
- Automatic `create` / `write` / `unlink` hooks (future CRUD pipeline).
- ACL checks inside `__call` (separate RFC / slice).
- Replacing static hooks with instance-only APIs.

## Background: MRO in Velm

**MRO** (*method resolution order*) is the ordered list of model classes for a logical model name:

```text
res.partner MRO (example):

  [0] Partner                    ← base (owns res_partner table)
  [1] PartnerExtension
  [2] PartnerChainedExtension    ← effective class (last registered)
```

- Extensions always `extend Model` and set `$inherit` — never `extend Partner`.
- Load order comes from manifest `depends()` (topological sort).
- **`static::super()`** and **`$recordset->method()`** both use this list.

See [Extension order (MRO)](../../website/docs/models/index.md#extension-order-mro) in the published docs.

## Design

### 1. Recordset dispatch (`Recordset::__call`)

```php
$partner = $env->model('res.partner')->browse($id);
$partner->badge();
```

Algorithm:

1. Load `extensionChainFor($modelName)` from the environment’s registry.
2. Walk **from effective class downward** (top of MRO to base).
3. Use the first class where `Model::isRecordMethod($name)` is true.
4. Invoke `$class::behavior()->$name($this, ...$userArgs)`.

`isRecordMethod` requires: public, non-static, declared on a class other than `Model`.

### 2. Behavior objects (`Model::behavior()`)

Each model class has one **stateless** behavior instance (singleton per class). Extensions get their own instance. No per-row model objects.

### 3. `super()` — static and instance

Single implementation: `protected static function super(...$args)`.

| Caller | Usage | Parent lookup |
|--------|--------|----------------|
| Static hook | `static::super($values)` | Nearest **static** implementor below caller in MRO |
| Instance method | `static::super($records, ...)` | Nearest **instance** implementor below caller; **first arg must be `Recordset`** |

**Important:** From instance methods, use `static::super($records)` — not `$this->super()`. PHP does not allow static and instance methods with the same name.

Middle extensions that do not implement a method are **skipped** when resolving the parent (unlike naive `Registry::superClass()` which only steps back one slot).

Legacy form still supported: `static::super(__FUNCTION__, ...)`.

### 4. Static hook dispatch (`Model::resolveStaticHookClass`)

Used by `Recordset::read()` and `RecordSerializer` for `display_name`:

```php
$hookClass = Model::resolveStaticHookClass($registry, 'res.partner', 'displayNameFor')
    ?? Model::class;
$hookClass::displayNameFor($row);
```

Walks MRO top → base; picks the first class that **declares** a public static method (declaring class ≠ `Model`).

This matches instance dispatch semantics and fixes extension stacks where the effective class does not override `displayNameFor`.

### 5. `Recordset::ensureOne()`

Guards singleton business logic:

```php
$records->ensureOne(); // throws if count !== 1
```

## Authoring guide

### Base model

```php
use Velm\Models\Model;
use Velm\Recordset\Recordset;

class Partner extends Model
{
  protected static ?string $name = 'res.partner';

  public function badge(Recordset $records): string
  {
    $records->ensureOne();
    return (string) ($records->read()[0]['name'] ?? '');
  }
}
```

### Extension

```php
final class PartnerChainedExtension extends Model
{
  protected static ?string $inherit = 'res.partner';

  public function badge(Recordset $records): string
  {
    $base = static::super($records); // → Partner::badge, skipping middle layers
    $records->ensureOne();
    $ref = (string) ($records->read()[0]['ref'] ?? '');
    return $ref === '' ? $base : $base.' · '.$ref;
  }
}
```

### Static hook (unchanged pattern)

```php
public static function displayNameFor(array $values): string
{
  $base = static::super($values);
  // ...
}
```

## Comparison with PyVelm

| | PyVelm | Velm (this RFC) |
|--|--------|------------------|
| Record handle | `BaseModel` instance (ids + env) | `Recordset` |
| Extension coupling | Python subclass replaces registry class; `super()` via MRO | Always `extends Model`; registry MRO + explicit `super()` |
| Business methods | Instance methods on merged class | Instance methods on behavior + `__call` |
| Load order | Module install / `_inherit` stack | Manifest `depends()` |

Velm trades Python’s native MRO for **explicit registry order** so independent addons never subclass each other.

## API surface (implemented)

| Symbol | Package | Role |
|--------|---------|------|
| `Recordset::__call` | `velm/core` | Dispatch instance methods |
| `Recordset::ensureOne` | `velm/core` | Singleton guard |
| `Model::behavior` | `velm/core` | Behavior singleton |
| `Model::isRecordMethod` | `velm/core` | Introspection for dispatch |
| `Model::resolveStaticHookClass` | `velm/core` | Static hook MRO resolution |
| `Model::super` | `velm/core` | MRO-aware chaining (extended) |

## Errors

| Situation | Exception |
|-----------|-----------|
| Unknown method on recordset | `BadMethodCallException` |
| `super()` on base with no parent | `LogicException` (no parent in MRO) |
| Instance `super()` without `Recordset` first arg | `LogicException` |
| `ensureOne()` on 0 or 2+ records | `InvalidArgumentException` |
| No active registry | `RuntimeException` (existing) |

## Testing

**128 tests** pass (`composer test`), including:

- `packages/core/tests/RecordsetMethodTest.php` — dispatch, super skip, args, legacy super, errors, static hook `read()` resolution.
- `packages/modules/tests/Feature/RecordsetMethodInheritTest.php` — real module install, `Partner::badge`, stacked extensions.
- Existing inherit tests unchanged in behavior (display_name / `super()` chains).

Fixture support classes under `packages/core/tests/Support/Country*Extension.php`.

## Migration / compatibility

- **Additive** — no breaking changes to existing manifests or models.
- Optional `badge()`-style methods on base models; extensions opt in.
- `display_name` on `read()` may change when the effective class did not override `displayNameFor` but a lower extension did — **bug fix**, not a regression for correctly layered stacks.

## Open questions

1. **Protected hooks** — Should `__call` allow `protected` methods for internal module use only?
2. **CRUD hooks** — `beforeWrite(Recordset $rs, array $values): array` as static or instance; same MRO rules?
3. **Multi-record methods** — Convention for methods that intentionally operate on 0..n ids (no `ensureOne`)?
4. **Server actions** — Wire `ir.actions.server` to `$recordset->{$method}()` with ACL (future).

## References

- Implementation: `packages/core/src/Models/Model.php`, `packages/core/src/Recordset/Recordset.php`
- User docs: `website/docs/models/method-overrides-and-super.md`
- PyVelm: `pyvelm/model.py` (`MetaModel._build_extension`, `BaseModel` recordset protocol)
- Prior art: [#54](https://github.com/velmphp/velm/pull/54) registry MRO + `static::super()` for static hooks only

## Decision

**Accept** — Ship with tests and docs. Revisit open questions when adding ACL-gated actions and CRUD hooks.
