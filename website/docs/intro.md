---
sidebar_position: 1
---

# Velm documentation

**Velm** is a Laravel-based ERP framework with PyVelm-style semantics: Odoo-like modules, recordsets, and view inheritance, rendered through the Velm admin shell (Livewire + Tailwind).

**Install from Packagist:** `composer create-project velmphp/app` — see [Installation](./guides/installation). Release tagging: [RELEASE.md](https://github.com/velmphp/velm/blob/main/RELEASE.md).

This site documents how to **author modules** and use the ORM. For repository architecture and contributor context, see the monorepo [PLAN.md](https://github.com/velmphp/velm/blob/main/PLAN.md) and [CONTEXT.md](https://github.com/velmphp/velm/blob/main/CONTEXT.md).

## Documentation map

| Topic | Where to start |
|-------|----------------|
| **Install** — `composer create-project velmphp/app` | [Installation](./guides/installation) |
| App addons and autoloading | [App addons](./guides/addons) |
| Schema diff, versioned migrations, hooks | [Module migrations](./guides/migrations) |
| Scaffold modules, models, views, menus | [Scaffolding](./guides/scaffolding) |
| Timestamps, catalog, branding, ACL UI, relations | [Platform features](./guides/features) |
| Panel navigation, apps catalog, branding | [Admin panel](./guides/admin-panel) |
| List search/filters, forms, M2M dialogs | [Views and forms](./guides/views-and-forms) |
| Models — fields, registry, recordsets | [Models](./models/) |
| Add a new business model and table | [Defining models](./models/defining-a-model) |
| Many2one, One2many, Many2many | [Relational fields](./models/relational-fields) |
| Access rights and record rules | [Security](./models/security) |
| Extend another module's model | [Extending models](./models/extending-a-model) |
| Override methods and chain behavior | [Method overrides and `super()`](./models/method-overrides-and-super) |
| Multiple addons on one model | [Stacking extensions](./models/stacking-extensions) |

## Models vs views

Velm has **two** inheritance mechanisms:

- **Model `$inherit`** — add columns and PHP methods to an existing model (same database table). Covered under **Models**.
- **View `VIEW_INHERITS`** — patch list/form arch JSON without forking views via `InheritView` (see [Views and forms — View inheritance](./guides/views-and-forms#view-inheritance)).

Model inheritance is data and server logic; view inheritance is UI layout.

## PyVelm reference

The Python reference implementation lives at [github.com/coolsam726/pyvelm](https://github.com/coolsam726/pyvelm). Concept names (`_inherit`, `super()`, module install order) align with PyVelm; PHP APIs use `$inherit`, `static::super()`, and `__velm__.php` manifests.
