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
| Registry MRO | Ordered list of base + extensions; drives field merge and `super()`. |
| `static::super(...)` | Call the previous layer's method in the MRO. |

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

Extension models call `registerExtension()` instead of `register()`. The registry merges fields and appends the class to the MRO chain. The **effective** model class (top of the chain) is what `env->model('res.partner')` uses for recordset dispatch.

## Related APIs

- `env->model('res.partner')->create([...])` — create records.
- `env->model('res.partner')->search([...])` — domain search.
- `GET /api/records?model=res.partner` — HTTP JSON API (`velm-web`).

View inheritance (`VIEW_INHERITS`) will be documented separately when the view guides land on this site.
