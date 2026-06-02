# Velm — agent & contributor context

Start with **[PLAN.md](./PLAN.md)** for the full architecture (ORM, modules, views, migrations, CLI, packages).

PyVelm reference implementation: `/home/smaosa/project-pyvelm` (or https://github.com/coolsam726/pyvelm).

## Package dependency order

`core` → `views` → `modules` → (`console` | `web` → `ui` → `filament`) → `framework`

## Git workflow

- Work lands via **feature branch → PR** (see [ROADMAP.md](./ROADMAP.md)).
- Branch names: `feature/<short-topic>` (e.g. `feature/partners-module-install`) — **no phase numbers** in branch or PR titles.
- Phase tracking stays in [ROADMAP.md](./ROADMAP.md) / [PLAN.md](./PLAN.md) only.

## Key conventions

- Composer vendor: `velmphp/*`
- PHP namespace: `Velm\`
- Module manifests: `__velm__.php` — use `Velm\Modules\Manifest::make('name')->version(…)->…` (fluent builder; plain arrays still supported)
- Model fields: prefer fluent setters on `Velm\Fields\*` (e.g. `CharField::make()->required()->maxLength(2)`); constructor/`make()` args still work
- Models: `$name` registers a table; `$inherit` on a class that **extends `Model`** adds fields and joins the registry MRO — call `static::super(...$args)` to chain overrides (load order between extensions is handled by the registry); `ALTER TABLE` on install (`partners_ext` fixture)
- Views: module `DATA` files return `ViewsData::make()->views(…)->inherits(…)->menus(…)`; synced to `ir.ui.view` / `ir.ui.menu`; shell nav from `MenuTreeBuilder` + `MenuLayoutContext` (Blade layout `velm-filament::layouts.velm-app`, primary Filament Amber, accent `#F1511B`); Filament list pages use PyVelm-style search toolbar (filters, group by, columns); Filament renders arch pages inside the shell; `GET /api/views/{module}/{name}` returns resolved arch JSON; `GET /api/records?model=&domain=&fields=` lists rows; `POST/PATCH/DELETE /api/records` for CRUD; `GET /api/m2o/search?model=&q=` and `POST /api/m2o/quick-create` for combobox options (`velm-web`)
- Runnable app: `apps/skeleton` — `composer run setup` then `composer run dev`; Artisan `velm:migrate`, `velm:module:*`
- Bundled module **code** lives under `packages/modules/modules/{name}/` (`models/`, `data/`, …); package **runtime** lives in `packages/modules/src/` (see `packages/modules/modules/README.md`)
- Module install state: `ir.module` (not Composer)
- CLI: `php velm …` (Artisan-style colon commands)
- Cron: Laravel Scheduler + `velm:cron:run`
