---
sidebar_position: 1
---

# Velm documentation

**Velm** is a Laravel-based ERP framework with PyVelm-style semantics: Odoo-like modules, recordsets, and view inheritance, rendered through a Filament-backed admin shell.

This site documents how to **author modules** and use the ORM. For repository architecture and contributor context, see the monorepo [PLAN.md](https://github.com/velmphp/velm/blob/main/PLAN.md) and [CONTEXT.md](https://github.com/velmphp/velm/blob/main/CONTEXT.md).

## Documentation map

| Topic | Where to start |
|-------|----------------|
| Install the skeleton app and modules | [Installation](./guides/installation) |
| Scaffold modules, models, views, menus | [Scaffolding](./guides/scaffolding) |
| Models — fields, registry, recordsets | [Models](./models/) |
| Add a new business model and table | [Defining models](./models/defining-a-model) |
| Extend another module's model | [Extending models](./models/extending-a-model) |
| Override methods and chain behavior | [Method overrides and `super()`](./models/method-overrides-and-super) |
| Multiple addons on one model | [Stacking extensions](./models/stacking-extensions) |

## Models vs views

Velm has **two** inheritance mechanisms:

- **Model `$inherit`** — add columns and PHP methods to an existing model (same database table). Covered under **Models**.
- **View `VIEW_INHERITS`** — patch list/form arch JSON without forking views. (Documentation coming soon.)

Model inheritance is data and server logic; view inheritance is UI layout.

## PyVelm reference

The Python reference implementation lives at [github.com/coolsam726/pyvelm](https://github.com/coolsam726/pyvelm). Concept names (`_inherit`, `super()`, module install order) align with PyVelm; PHP APIs use `$inherit`, `static::super()`, and `__velm__.php` manifests.
