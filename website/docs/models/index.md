---
sidebar_position: 1
---

# Models

Velm models are plain PHP classes backed by a **registry**, an **environment** (`env`), and **recordsets** for CRUD and search. Each model maps to one database table unless you extend an existing model with `$inherit`.

## Concepts

| Concept | Meaning |
|---------|---------|
| `$name` | Dotted model id (e.g. `res.partner`). Required on base models. |
| `$table` | SQL table name (defaults to dots → underscores). |
| `defineFields()` | Returns `Velm\Fields\*` descriptors bound to columns. |
| `$inherit` | Extend an existing model from another module — no new table. |
| [Extension order (MRO)](#extension-order-mro) | Which module's class runs first when several extend the same model. |
| `static::super(...)` | Call the next implementor of the same method down the MRO. |
| `$recordset->method()` | Dispatch a public instance method on the model behavior object. |

## Extension order (MRO)

**MRO** means **method resolution order**: the sequence Velm uses to decide *which class implements a method* when more than one module extends the same model (for example `res.partner`).

Velm does **not** rely on PHP `extends` between addon classes. Each extension is `class X extends Model` with `$inherit = 'res.partner'`. The **registry** keeps an explicit ordered list instead:

```text
MRO for res.partner (example):

  [0] partners\Partner              ← base (owns the table)
  [1] partners_ext\PartnerExtension
  [2] partners_tags\PartnerTagsExtension   ← effective (top) class
```

| Term | Meaning |
|------|---------|
| **MRO chain** | The full list above, in load order. |
| **Effective class** | The last entry — what `env->model('res.partner')` uses for dispatch. |
| **Previous class** | The entry before yours in the chain — what `static::super()` calls. |

**Load order** comes from manifest `depends()` (topological sort). If module B depends on module A, A's extension is always earlier in the MRO than B's.

That order drives three behaviors:

1. **Fields** — merged in chain order; a later module can override the same field name.
2. **`static::super()`** — your method runs first; `super()` delegates to the previous class's method with the same name.
3. **Runtime dispatch** — framework code calls the effective class, not the base alone.

This is the same idea as Python's MRO on a subclass stack, but Velm builds the list at **module install** time so independent addons never need to `extend` each other's PHP classes.

## In this section

Pages are ordered from foundational to advanced — similar to Laravel's Eloquent docs.

| Guide | You will learn |
|-------|----------------|
| [Defining models](./defining-a-model) | Create a base model, register it, install the module. |
| [Extending models](./extending-a-model) | Add fields to an existing model with `$inherit`. |
| [Method overrides and `super()`](./method-overrides-and-super) | Customize hooks such as `displayNameFor` and chain to the base. |
| [Stacking extensions](./stacking-extensions) | Several addons on one model, load order, and `depends`. |

## Module install lifecycle

When `module:install` runs:

```text
discover manifests → topo-sort DEPENDS → for each module:
  load models into Registry
  apply schema (CREATE TABLE / ADD COLUMN)
  sync views & menus
```

Extension models call `registerExtension()` instead of `register()`. The registry merges fields and appends the class to the [MRO chain](#extension-order-mro). The **effective** model class (top of the chain) is what `env->model('res.partner')` uses for recordset dispatch.

## Related APIs

- `env->model('res.partner')->create([...])` — create records.
- `env->model('res.partner')->search([...])` — domain search.
- `GET /api/records?model=res.partner` — HTTP JSON API (`velm-web`).

View inheritance (`VIEW_INHERITS`) will be documented separately when the view guides land on this site.
