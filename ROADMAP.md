# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR** (see [CONTEXT.md](./CONTEXT.md) for branch/PR naming).

## Stable v1.0 target

**Stable** means a third-party developer can install `velmphp/app`, author modules, run the panel in production, and reset dev databases without monorepo-only hacks.

| Milestone | Goal |
|-----------|------|
| **v1.0-rc1** | Installable, documented, resettable dev DB |
| **v1.0-rc2** | Addon-author ORM parity (compute, domain, O2M inline) |
| **v1.0** | Tag `velmphp/*@1.0.0` + production ops guide |
| **v1.1** | Kanban + reporting views |

### Tier 1 — Release blockers (v1.0-rc1)

| # | Item | Status | Notes |
|---|------|--------|-------|
| 1.1 | `velm:migrate:fresh` | **Done** | Dev reset: drop Velm schema + reinstall bootstrap — see [RC1 slice](#rc1-slice--migratefresh--seed) |
| 1.2 | `velm:seed` + manifest `SEEDERS` | **Done** | Module-scoped seeders in topo order — see [RC1 slice](#rc1-slice--migratefresh--seed) |
| 1.3 | Packagist-ready `velmphp/framework` + tagged releases | **In progress** | `^1.0@dev` constraints, `velmphp/app` without path repos; tag `v1.0.0-rc1` + org registration remain |
| 1.4 | Production ops guide (cron, attachments disk, DB choice) | Pending | `website/docs/guides/` |
| 1.5 | CI matrix (PHP 8.3+, SQLite + MySQL/Postgres smoke) | Pending | Extend root `composer test` |
| 1.6 | Docs / ROADMAP sync | Ongoing | `intro.md`, `CONTEXT.md`, guides |

### Tier 2 — Core parity (v1.0-rc2 → v1.0)

| # | Item | Status | Notes |
|---|------|--------|-------|
| 2.1 | Computed fields (`@depends`, stored / unstored) | Pending | ORM parity; list/form columns |
| 2.2 | Domain OR-groups | Pending | Search + record rules |
| 2.3 | `mail.thread` mixins (`$mixins`) | Pending | Replace interim `$mailThread` |
| 2.4 | O2M inline / table widget | Pending | Dialog mode shipped |
| 2.5 | `file` / `files` field widgets | Pending | Attachments on arbitrary models |
| 2.6 | Uninstall optional schema cleanup (`--drop-schema`) | Pending | Dev-only; tables kept by default |

### Tier 3 — Shell polish (v1.0 or v1.0.1)

| Item | Status | Notes |
|------|--------|-------|
| `header_actions` / `page_actions` from arch | Pending | Export, duplicate, custom toolbar |
| Kanban view renderer | Pending | Arch type exists; no Livewire renderer |
| Arch `dashboard` view type (per-model boards) | Pending | Distinct from **home dashboard** (`/velm/dashboard`) |
| List inline row edit | Pending | PLAN Phase 4+ |

### Tier 4 — Post-stable (v1.1+)

| Item | Notes |
|------|-------|
| Graph / pivot views | Reporting |
| `velmphp/composer-plugin` (`type: velm-module`) | Marketplace-style addons |
| Publish `velmphp/app` on Packagist | Monorepo copy at `apps/app/`; demos in `apps/demo/` |
| Filament arch adapter | Superseded by `velm-ui` |

---

## RC1 slice — `migrate:fresh` + `seed`

**Branch:** `feature/velm-migrate-fresh-seed` (suggested)  
**Goal:** Reliable dev/CI reset without hand-dropping tables.

### A. `velm:migrate:fresh`

| Step | Work |
|------|------|
| A1 | `VelmSchemaReset` in `packages/modules` — discover all model tables + M2M junctions from addon roots; `DROP TABLE` in safe order (junctions → models → `ir_*` metadata). **Do not** drop Laravel `users` / `sessions` / `jobs` unless `--purge-app` (optional, defer). |
| A2 | Clear `ir_module` rows (or drop + recreate `ir_module` via existing migration). |
| A3 | `MigrateFreshCommand` — `velm:migrate:fresh {--yes} {--modules=*}` — confirm prompt unless `--yes`; call reset → `installBootstrap` (default `base`, `admin`) → optional `--modules=partners,workflow,…` like demo setup. |
| A4 | Wire in `VelmServiceProvider`; document in `apps/app/README.md` + `website/docs/guides/installation.md`. |
| A5 | Feature test: install partners → fresh → assert `ir_module` + `res_partner` recreated empty. |

**PyVelm parity:** `migrate:fresh` = drop Velm DDL + reinstall; not `migrate:reset` (uninstall all modules).

### B. `velm:seed` + manifest `SEEDERS`

| Step | Work |
|------|------|
| B1 | `Manifest::seeders(...)` + `ModuleSpec::$seeders` + manifest reader. |
| B2 | `ModuleSeeder` contract: `public static function run(Environment $env): void` (idempotent). |
| B3 | `ModuleSeederRunner` — topo order over **installed** modules; invoke each seeder class. |
| B4 | `SeedCommand` — `velm:seed {--module=}` — all installed or one module + deps. |
| B5 | First fixture: `partners` demo countries/partners seeder (or extract from SYNC_HOOK if too heavy). |
| B6 | Optional: `velm:migrate --seed` flag runs seeders after install (defer if scope tight). |
| B7 | Tests: seed idempotency (run twice, same row counts). |

**Out of scope for this slice:** Packagist tagging (1.3), production ops doc (1.4), computed fields (2.1).

### Suggested PR checklist

- [x] `VelmSchemaReset` + tests
- [x] `velm:migrate:fresh` command + app/demo docs
- [x] `SEEDERS` manifest key + `ModuleSeederRunner`
- [x] `velm:seed` command + one bundled seeder
- [ ] `ROADMAP.md` / `CONTEXT.md` status bumps

---

## Phase 0 — Module runtime

| Item | Status |
|------|--------|
| `__velm__.php` manifest reader + `ModuleSpec` | Done |
| Discovery + topological sort | Done |
| `ir.module` table + repository | Done |
| `php artisan velm:module:*` / `velm:migrate` / `velm:db:*` | Done (Laravel app required) |
| Bundled `base` + `admin` manifests | Done |
| Apps catalog UI (`/velm/apps`, install/sync/upgrade/**uninstall**, module detail) | Done |
| Runnable Velm app — `velmphp/app` (`apps/app`) + demo (`apps/demo`) | Done |
| DATA / VIEW sync on install | Done |
| Module uninstall (CLI + Apps UI, dependency blockers) | Done |

## Phase 1 — ORM foundation

| Item | Status |
|------|--------|
| `Environment`, `Registry`, `RecordCache` | Done |
| Field types: Char, Integer, Boolean, Text, Many2one | Done |
| Fluent field + manifest builders | Done |
| `Model` + `Recordset` (`create` / `read` / `write` / `search`) | Done |
| PDO SQLite adapter + schema builder (tests) | Done |
| Additive schema column diff on install | Done |
| Bundled `partners` module (`res.partner`, `res.country`) | Done |
| Model registration + schema on `module:install` | Done |
| Laravel DB connection (`LaravelConnection`) | Done |
| `VelmManager` + `Environment` container binding | Done |
| Livewire arch pages (list / create / edit) in `velmphp/admin` | Done |
| `res.company` on base module + default company on install | Done |
| Field `displayLabel()` / form list label humanization | Done |

## Phase 3b — Attachments & file manager (in progress)

| Item | Status |
|------|--------|
| `ir.attachment` model + storage (Laravel Flysystem disk / db inline / legacy local path) | Done |
| `POST/GET/DELETE /api/attachment/*` | Done |
| `file_manager` module (`res.attachment.folder`, ACL install hook, list views) | Done |
| Drive-style library shell (`/web/files/library`) | Done |
| File picker widgets (`file`, `files`) | Pending |
| `file_url` widget (company logos / favicon via library picker) | Done |
| Bulk actions, properties page, Alpine `pvFileLibrary` | Done |
| View/menu sync prunes stale views removed from disk | Done |

## Phase 3 — Views and menus

| Item | Status |
|------|--------|
| `ir.ui.view` model + DATA file loader | Done |
| View sync on `module:install` / `module:sync` | Done |
| Fluent `ListView` / `FormView` / `Field` authoring | Done |
| `ViewRegistry` + Livewire pages load stored arch | Done |
| `VIEW_INHERITS` + `resolve_arch()` | Done |
| Fluent `InheritView` authoring (`updateSection`, `afterField`, …) | Done |
| Third-party inherit order (module `depends` + skip missing targets) | Done |
| `ir.ui.menu` + navigation from menus | Done |
| Breadcrumbs on list/form/dashboard pages | Done |
| PyVelm-style shell (apps rail + top bar / sidebar layout) | Done |
| Shell UI tokens + layout (PyVelm indigo theme, nav-item styles) | Done |
| `GET /api/views` | Done |
| `GET /api/records` | Done |
| `POST/PATCH/DELETE /api/records` | Done |
| `GET /api/m2o/search` | Done |
| PyVelm-style list search toolbar (search, filters, group by, columns) | Done |
| Arch form many2one combobox (`Many2oneSearch`) | Done |
| `POST /api/m2o/quick-create` | Done |
| `DetailView` authoring + stored detail routes | Done |
| List row actions (`ListRowAction`, icons, ACL-gated delete) | Done |
| List click-to-open via `detailView` + `ResolvesStoredView` | Done |
| Form layout (`cols`, `colspan`, Ctrl+S) | Done |
| Company branding (`CompanyBranding`, `res.company` fields) | Done |
| Apps catalog + module rail **Dashboard** entry | Done |
| `res.users` on Laravel `users` + bootstrap admin env | Done |
| ACL admin UI (users, groups, model access, rules) | Done |
| O2M / M2M record dialog + `?embed=1` form save bar | Done |
| `demo_relations` + `partners_ext` demo addons (`apps/demo`) + website docs | Done |

## Phase 3c — Home dashboard

| Item | Status |
|------|--------|
| `/velm` → `/velm/dashboard` default home | Done |
| `DashboardData` / widget specs in modules | Done |
| `DashboardCollector` + ACL-gated widgets in admin | Done |
| Module `dashboard.php` hook (partners, workflow, change_management) | Done |

## Phase 5 — Workflows

| Item | Status |
|------|--------|
| `workflow` module (definition, instance, approval, task models) | Done |
| Workflow engine port (schema v1, transitions, approvals, sequential/all/any) | Done |
| `change_management` demo addon (`it.change` + ICT lifecycle workflow) | Done |
| Record detail workflow panel + `/web/workflow/*` API | Done |
| Approval inbox (`/web/workflow/inbox`) | Done |
| Overdue approval cron (`workflow_escalate` server action) | Done |
| Visual workflow designer (PyVelm builder) | Done |
| Rich transition / approval dialogs on record pages | Done |

## Phase 2+

| Item | Status |
|------|--------|
| Model `$inherit` (field extensions, `static::super()`, stacked extensions) | Done |
| Model record methods (`$recordset->method()`, MRO dispatch) | Done — [RFC 0001](./docs/rfcs/0001-model-record-methods.md) |
| ACL (`ir.model.access`, superuser bypass, Recordset gates) | Done |
| Record rules (`ir.rule`, domain injection) | Done — [RFC 0002](./docs/rfcs/0002-record-rules.md) |
| Schema migrations (`db:diff`, `db:status`, versioned scripts, upgrade on migrate) | Done (slices 1–2) |
| `INSTALL_HOOK` / `SYNC_HOOK`, Velm `Schema` API, `ir.cron` + `velm:cron:run` | Done (`php artisan velm:cron:run`) |
| `velm:make:module` scaffold | Done |
| `velm:make:model` scaffold | Done |
| `velm:make:view` scaffold | Done |
| `velm:make:menu` scaffold | Done |
| `velm:db:autogen --with-views` | Done |
| One2many / Many2many on recordsets | Done |
| Relational fields docs | Done |
| `velm:migrate:fresh` | **RC1 — done** |
| `velm:seed` + manifest `SEEDERS` | **RC1 — done** |

## Phase 4e — Mail thread & chatter (in progress)

| Item | Status |
|------|--------|
| `mail` module (`mail.message`, `mail.follower`) | Done |
| `mail.thread` opt-in via model `$mailThread = true` | Done (interim; see mixins below) |
| Chatter sidebar on record display (messages, follow, post) | Done |
| `it.change` wired with chatter | Done |
| Abstract model mixins (`mail.thread` via `$mixins` / registry) | Deferred → Tier 2 |

## Backlog (was “Next up”)

See [Stable v1.0 target](#stable-v10-target) tiers above. Remaining items not in rc1/rc2:

| Item | Tier |
|------|------|
| Kanban / graph / pivot arch renderers | 3 / 4 |
| O2M inline widget | 2 |
| `header_actions` / `page_actions` | 3 |
| Computed fields + domain OR-groups | 2 |
| Packagist split + `velmphp/app` | 1 / 4 |
| Optional Filament arch adapter | — (won't do) |

See [PLAN.md](./PLAN.md) for the long-range design; user-facing behavior is in `website/docs/guides/`.
