---
sidebar_position: 1
---

# Velm documentation

**Velm** is a Laravel-based ERP framework with PyVelm-style semantics: Odoo-like modules, recordsets, and view inheritance, rendered through the Velm admin shell (Livewire + Tailwind).

**Install from Packagist:** `composer create-project velmphp/app my_app` — see [Installation](./guides/installation). Use the **version dropdown** above for docs tied to a release tag (e.g. **1.0.1**); **Next** tracks unreleased changes on `main`.

## Developer journey

If you are new to Velm, think in three steps — not nine packages.

![Velm developer journey: a stick figure walks from create-project through module authoring to Laravel deploy](/img/developer-journey.svg)

| Step | What you do | What you get |
|------|-------------|--------------|
| **Start** | `composer create-project velmphp/app` → `composer run setup` | A Laravel app with the Velm panel at `/velm` and bootstrap modules installed |
| **Code** | Add an addon under `addons/` — models, `views/*.php` (list, form, menus), then `velm:module:sync` | Working list and form pages in the shell — no hand-built CRUD or Filament resources |
| **Ship** | Deploy like any Laravel app; run `php artisan velm:migrate` on the server | The same `/velm` UI against your production database; modules are opt-in **per database** |

**Why it feels easy:** you stay in one stack (Composer, Artisan, Eloquent connections, your host). Velm adds module install/sync and declarative view arch — you do not run a parallel ERP runtime beside Laravel.

Next reads: [Installation](./guides/installation) → [Scaffolding](./guides/scaffolding) → [App addons](./guides/addons) → [Production](./guides/production).

The doodle is generated with [roughjs](https://roughjs.com) (the same sketch engine Excalidraw uses). Regenerate after editing `website/scripts/generate-developer-journey-svg.mjs`:

```bash
cd website && npm run generate:journey-svg
```

For hand-tweaks in [Excalidraw](https://excalidraw.com), import `website/static/img/developer-journey.svg`, edit, and export — or adjust the generator script and re-run the command above.

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
| Production deploy, cron, attachments | [Production](./guides/production) |
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
