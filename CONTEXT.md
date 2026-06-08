# Velm — agent & contributor context

Start with **[PLAN.md](./PLAN.md)** for the full architecture (ORM, modules, views, migrations, CLI, packages). **What is shipped today** is summarized in [ROADMAP.md](./ROADMAP.md) and the *Implementation snapshot* section in PLAN.md.

PyVelm reference implementation: `/home/smaosa/project-pyvelm` (or https://github.com/coolsam726/pyvelm).

## Package dependency order

`core` → `views` → `modules` → (`console` | `web` → `ui` → `admin`) → `framework`

| Package | Role |
|---------|------|
| `velmphp/core` | ORM, Environment, Registry, fields, domain, ACL helpers |
| `velmphp/views` | `ir.ui.view` / menu sync, arch resolve, authoring builders |
| `velmphp/modules` | Loader, `ir.module`, schema diff, migrations, bundled `modules/` |
| `velmphp/web` | Routes, Environment middleware, JSON APIs |
| `velmphp/ui` | Shell Blade, list/form widgets, Tailwind (`velm.src.css`), record dialog |
| `velmphp/admin` | Livewire pages (`ArchListPage`, stored views, apps catalog) — **not Filament** |
| `velmphp/framework` | Metapackage + service provider |

`packages/filament/` is a legacy mirror of early panel code; **`apps/app` and `apps/demo` depend on `velmphp/admin` only.**

## Git workflow

- Work lands via **feature branch → PR** (see [ROADMAP.md](./ROADMAP.md)).
- Branch names: `feature/<short-topic>` (e.g. `feature/partners-module-install`) — **no phase numbers** in branch or PR titles.
- Phase tracking stays in [ROADMAP.md](./ROADMAP.md) / [PLAN.md](./PLAN.md) only.

## Runnable app & docs

- **App:** `velmphp/app` (monorepo: `apps/app`) — minimal `create-project` template; bootstrap only.
- **Demo:** `velmphp/velm-demo` (monorepo: `apps/demo`) — `composer run setup` then `composer run dev` → `/velm` with reference modules.
- **Bootstrap modules:** `base`, `admin`, `geo_data`, `file_manager` (via `velm.bootstrap_modules`); `partners` depends on `geo_data`; file pickers require `file_manager`.
- **CLI:** `php artisan velm:*` only (no standalone `bin/velm` in production path).
- **User docs:** `website/` (Docusaurus) — guides [installation](website/docs/guides/installation.md), [addons](website/docs/guides/addons.md), [admin-panel](website/docs/guides/admin-panel.md); build with `cd website && npm run build`.
- **Demo addon:** `apps/demo/addons/demo_relations` — M2O / O2M / M2M under **Demos** menu.

## Admin shell (current)

- **Panel path:** `/velm` (`velm.panel_path`); home → `/velm/dashboard`.
- **Layouts:** `VELM_MENU_LAYOUT=apps` (default: module rail + secondary top bar) or `sidebar` (nested accordion); per-company override on `res.company.menu_layout`.
- **Apps catalog:** `AppsCatalog` + Livewire `AppsPage` / `AppsDetailPage`; dedicated catalog sidebar (`AppsCatalogMenuContext`); module rail entry **Apps** (Level 2, no section).
- **Stored views:** `/velm/views/{module}/{view}` (list), `…/{id}` (detail), `…/edit`, `…/create` — `ResolvesStoredView` + `StoredViewRoutes`; list pages must not override detail URLs if `clickToOpen()` is used.
- **Branding:** `CompanyBranding` — company `app_name`, logos, `primary_color`; env `VELM_APP_NAME` / `VELM_LOGO_*`; header brand links to apps catalog.
- **Users:** `res.users` model table **`users`** (Laravel); bootstrap `VELM_ADMIN_EMAIL` / `VELM_ADMIN_PASSWORD` in `config/velm.php`; ACL admin UI uses `email`.
- **CSS:** edit `packages/ui/resources/css/velm.src.css`, then `composer run velm-rebuild-ui` from the monorepo root (or `composer run build-ui` if you only need the package build).

## Key conventions

- Composer vendor: `velmphp/*`
- PHP namespace: `Velm\`
- Module manifests: `__velm__.php` — use `Velm\Modules\Manifest::make('name')->version(…)->…` (fluent builder; plain arrays still supported)
- App addon PHP: `addons/{module}/` → `Addons\{StudlyModule}\…` — runtime autoload (no per-addon `composer.json` PSR-4); bundled modules use `Velm\Modules\{StudlyModule}\…`
- Model fields: prefer fluent setters on `Velm\Fields\*` (e.g. `CharField::make()->required()->maxLength(2)`); constructor/`make()` args still work
- Models: `$name` registers a table; base models get `id`, `display_name`, `created_at`, `updated_at` automatically (`$timestamps = false` to opt out); `$inherit` on a class that **extends `Model`** adds fields and joins the registry MRO — chain static hooks and instance methods with `static::super(...$args)` (recordset methods: `static::super($recordset, ...)`); `$recordset->action()` dispatches via `Recordset::__call`; `ALTER TABLE` on install (`partners_ext` fixture)
- Views: module `views/*.php` return `ViewsData::make()->views(…)->inherits(…)->menus(…)`; synced to `ir.ui.view` / `ir.ui.menu`
- **Authoring:** `ListView`, `FormView`, `DetailView`, `ListRowAction`, `Action` (`url`, `form()`, `formView()`, `variant(ActionVariant)`, `confirm`, `perm`), `ActionForm` (inline action schema), `Field::colspan()`, `FormView::cols()`, `->clickToOpen()`, `->detailView()`
- **List presentation:** `InteractsWithVelmListPresentation` — auto **Delete** when `perm_unlink`; Open/Edit gated by ACL; icon row actions in `velm-ui` list row partial
- **Forms:** `#velm-form` + Ctrl+S in `form-scripts.blade.php`; embed mode `?embed=1` for record dialog — full action bar, `velm-dialog-saved` postMessage to parent for M2M chips
- **Relational UI:** M2O via `/api/m2o/search`; O2M/M2M default **dialog** widget; `pvOpenRecord()` / Alpine `recordDialog` store
- APIs (`velm-web`): `GET /api/views`, `GET/POST/PATCH/DELETE /api/records`, `GET /api/m2o/search`, `POST /api/m2o/quick-create`
- Bundled module **code:** `packages/modules/modules/{name}/`; runtime: `packages/modules/src/`
- Module install state: `ir.module` (not Composer)
- Cron: Laravel Scheduler + `php artisan velm:cron:run`

## Release path

**v1.0.0** is stable — see [ROADMAP.md](./ROADMAP.md) *Stable v1.0 target*.

| Milestone | Focus |
|-----------|-------|
| **rc1–rc3** | Install, Packagist, full 1.0 feature set — **done** |
| **1.0.0** | Stable tag; `composer create-project velmphp/app`; `^1.0` constraints — **done** |

Tag flow: [RELEASE.md](./RELEASE.md) + `npm run docs:version` per release.

## Writing tests

Monorepo tests use **Pest** (`composer test`). Coverage scope is defined in [phpunit.xml](phpunit.xml) (`packages/*/src`, bundled modules).

| Package | Extend | Notes |
|---------|--------|-------|
| `web` | `Velm\Web\Tests\TestCase` | HTTP feature tests; installs `base`, `admin`, `partners` |
| `admin` | `Velm\Admin\Tests\TestCase` | Livewire + HTTP |
| `framework` | `Velm\Framework\Tests\TestCase` | `$this->artisan('velm:…')` |
| `console` | `Velm\Console\Tests\ConsoleTestCase` | Symfony `CommandTester` |
| `modules` / `views` | `Velm\Modules\Tests\TestCase` | `ModuleInstaller` + SQLite `:memory:` |
| `core` / `ui` | Pure unit | Minimal env |

Local coverage requires **pcov** (`apt install php8.4-pcov` or `php8.3-pcov`). Run `composer test:coverage:report` (currently enforces **95%** minimum; raise toward **99%** per phase). CI uploads `coverage.xml` and reports to Codecov.

## Still open (do not assume done)

- **Post-1.0** (Tier 3): list inline edit — `header_actions` / `page_actions` and per-model arch `dashboard` **done** in 1.1
- Filament arch adapter (optional; not used by app/demo)
