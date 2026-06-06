# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR** (see [CONTEXT.md](./CONTEXT.md) for branch/PR naming).

## Stable v1.0 target

**Stable** means a third-party developer can install `velmphp/app`, author modules, run the panel in production, and reset dev databases without monorepo-only hacks.

We ship **feature release candidates** before `v1.0.0` — each RC is tagged on `main`, split to Packagist mirrors, and snapshotted in docs (`npm run docs:version`). See [RELEASE.md](./RELEASE.md) and [website/DOCS_MAINTAINERS.md](./website/DOCS_MAINTAINERS.md).

| Milestone | Goal | Status |
|-----------|------|--------|
| **v1.0-rc1** | Installable, documented, resettable dev DB | **Done** |
| **v1.0-rc2** | Packagist publishing, MIT, install/CI fixes | **Done** |
| **v1.0-rc3** | Full 1.0 feature set — ORM, widgets, ops, kanban/graph/pivot | Planned |
| **v1.0.0** | Stable tag, `^1.0` constraints, no `-s rc` for `create-project` | Target |

### Release candidate plan (pre-1.0)

Each RC = feature PR(s) on `main` → CI green → `CHANGELOG` → `git tag v1.0.0-rcN` → Packagist verify → `docs:version` → GitHub pre-release.

| RC | Tier items | Outcome for addon authors |
|----|------------|---------------------------|
| **rc3** | [2.1–2.9](#tier-2--core-parity-pre-10), [1.4](#tier-1--release-blockers-pre-10), [1.5](#tier-1--release-blockers-pre-10) | Computed fields; OR-groups; `$mixins`; O2M inline + file widgets; `--drop-schema`; production runbook; DB CI; kanban/graph/pivot + `read_group` |
| **1.0.0** | Tier 1.6, constraint tighten, final smoke | Stable Packagist; plain `create-project velmphp/app` |

**Branch naming:** `feature/rc3-v1-features` — thematic PRs on one branch where practical.

**Deferred past v1.0.0** (Tier 3 → 1.0.1 or 1.1): `header_actions`, list inline edit, per-model arch `dashboard` boards.

### Tier 1 — Release blockers (pre-1.0)

| # | Item | Status | Notes |
|---|------|--------|-------|
| 1.1 | `velm:migrate:fresh` | **Done** | Dev reset: drop Velm schema + reinstall bootstrap — see [RC1 slice](#rc1-slice--migratefresh--seed) |
| 1.2 | `velm:seed` + manifest `SEEDERS` | **Done** | Module-scoped seeders in topo order — see [RC1 slice](#rc1-slice--migratefresh--seed) |
| 1.3 | Packagist-ready `velmphp/framework` + tagged releases | **Done** | rc1–rc2 shipped; see [RELEASE.md](./RELEASE.md) |
| 1.4 | Production ops guide (cron, attachments disk, DB choice) | **rc3** | Done — `website/docs/guides/production.md` |
| 1.5 | CI matrix (PHP 8.3+, SQLite + MySQL/Postgres smoke) | **rc3** | Done — MySQL/Postgres dialect smoke jobs in CI |
| 1.6 | Docs / ROADMAP sync | Ongoing | Per RC: `docs:version`, install guides, feature docs |

### Tier 2 — Core parity (pre-1.0)

Ship in **rc3** — last RC before stable.

| # | Item | RC | Status | Notes |
|---|------|-----|--------|-------|
| 2.1 | Computed fields (`@depends`, stored / unstored) | **rc3** | **Done** | ORM + list/form columns; fluent `depends()`; stored recompute on write |
| 2.2 | Domain OR-groups | **rc3** | **Done** | `\|` / `&` / `!` prefix notation; legacy `__or__`; search + record rules |
| 2.3 | `mail.thread` mixins (`$mixins`) | **rc3** | **Done** | `$mixins = ['mail.thread']`; abstract `MailThread` mixin registration |
| 2.4 | O2M inline / table widget | **rc3** | Pending | Dialog mode done; embedded sub-grid on forms |
| 2.5 | `file` / `files` field widgets | **rc3** | Pending | Attachments API done; form widgets + list column |
| 2.6 | Uninstall optional schema cleanup (`--drop-schema`) | **rc3** | **Done** | `velm:module:uninstall --drop-schema` (local/testing only) |
| 2.7 | Kanban view renderer | **rc3** | Pending | Arch type exists; Livewire board + card drag |
| 2.8 | Graph view renderer | **rc3** | Pending | `read_group` backend + chart Livewire page |
| 2.9 | Pivot view renderer | **rc3** | Pending | `read_group` + pivot grid Livewire page |

### Tier 3 — Shell polish (1.0.1 or 1.1)

Deferred past **v1.0.0**.

| Item | Status | Notes |
|------|--------|-------|
| `header_actions` / `page_actions` from arch | Pending | Export, duplicate, custom toolbar |
| Arch `dashboard` view type (per-model boards) | Pending | Distinct from **home dashboard** (`/velm/dashboard`) |
| List inline row edit | Pending | PLAN Phase 4+ |

### Tier 4 — Post-stable (v1.1+)

| Item | Notes |
|------|-------|
| `velmphp/composer-plugin` (`type: velm-module`) | Marketplace-style addons |
| Filament arch adapter | Superseded by `velm-ui` |

---

## RC1 slice — migrate:fresh + seed

**Status:** done · **Tag:** `v1.0.0-rc1`

**Goal:** Reliable dev/CI reset without hand-dropping tables.

---

## RC2 slice — Packagist + install (done)

**Tag:** `v1.0.0-rc2`

| Step | Work |
|------|------|
| R2.1 | MIT license; remove `"version"` from library `composer.json` (Packagist ↔ git tags) |
| R2.2 | `^1.0@dev` constraints; `composer.local.json.example` for monorepo path repos |
| R2.3 | Tracked root `composer.lock` + `config.platform.php: 8.3.31`; CI `fetch-depth: 0` |
| R2.4 | Docs versioning (`npm run docs:version`); install guides; `create-project … -s rc` |

Install: `composer create-project velmphp/app my_app v1.0.0-rc2 -s rc`

---

## RC3 slice — v1.0 feature set (planned)

**Branch:** `feature/rc3-v1-features` (suggested)  
**Tag:** `v1.0.0-rc3` — last RC before stable (consolidated rc3–rc5 + analytics views)

### ORM + domain

| Step | Work |
|------|------|
| R3.1 | `ComputedField` / `@depends` — stored vs unstored compute paths | **Done** |
| R3.2 | Registry + schema: optional stored columns; recompute on dependency write | **Done** |
| R3.3 | List/form read path includes computed values | **Done** |
| R3.4 | Domain compiler: OR (`\|`) and AND groups for search domains + `ir.rule` | **Done** |
| R3.5 | Tests: compute invalidation, OR-group search, record rules with `\|` | **Done** |

### Author UX + mixins

| Step | Work |
|------|------|
| R3.6 | `$mixins` / abstract model registration — migrate `$mailThread` to `mail.thread` mixin | **Done** |
| R3.7 | O2M inline/table Livewire widget on form arch |
| R3.8 | `file` + `files` form widgets wired to attachment API |
| R3.9 | Docs: models, views-and-forms, addons; demo addon coverage |

### Analytics views

| Step | Work |
|------|------|
| R3.10 | `read_group` on recordsets (groupby, aggregates, date trunc helpers) | **Done** — `Recordset::readGroup()` with sum/avg/min/max/count |
| R3.11 | Kanban Livewire renderer — card template, columns, drag reorder |
| R3.12 | Graph Livewire renderer — measures, chart library integration |
| R3.13 | Pivot Livewire renderer — row/col groupby grid |
| R3.14 | Stored routes for `view_type` kanban/graph/pivot; demo views + tests |

### Ops + stable prep

| Step | Work |
|------|------|
| R3.15 | `velm:module:uninstall --drop-schema` (dev-only flag) | **Done** |
| R3.16 | Production ops guide: cron, queues, attachment disk, MySQL/Postgres notes | **Done** |
| R3.17 | CI: MySQL + Postgres service containers; app-install + migrate smoke | **Done** |
| R3.18 | Regenerate `apps/app/composer.lock` from Packagist where needed |

**PyVelm parity:** `@depends`, domain prefix notation, `$mixins`, kanban/graph/pivot arch.

**Out of rc3:** per-model arch `dashboard` boards (Tier 3), `header_actions` (Tier 3).

---

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
- [x] `ROADMAP.md` / `CONTEXT.md` status bumps

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

| Item | RC |
|------|-----|
| Computed fields, OR-groups, mixins, O2M inline, file widgets | **rc3** |
| Kanban / graph / pivot + `read_group` | **rc3** |
| `--drop-schema`, production ops, DB CI | **rc3** |
| `header_actions` / list inline edit | post-1.0 (Tier 3) |
| Optional Filament arch adapter | — (won't do) |

See [PLAN.md](./PLAN.md) for the long-range design; user-facing behavior is in `website/docs/guides/`.
