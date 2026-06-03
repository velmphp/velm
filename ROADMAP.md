# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR** (see [CONTEXT.md](./CONTEXT.md) for branch/PR naming).

## Phase 0 — Module runtime

| Item | Status |
|------|--------|
| `__velm__.php` manifest reader + `ModuleSpec` | Done |
| Discovery + topological sort | Done |
| `ir.module` table + repository | Done |
| `php artisan velm:module:*` / `velm:migrate` / `velm:db:*` | Done (Laravel app required) |
| Bundled `base` + `admin` manifests | Done |
| Apps catalog UI (`/velm/apps`, sync/upgrade, module detail) | Done |
| Runnable skeleton app (`apps/skeleton`) | Done |
| DATA / VIEW sync on install | Done |

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
| `ViewRegistry` + Filament pages load stored arch | Done |
| `VIEW_INHERITS` + `resolve_arch()` | Done |
| `ir.ui.menu` + navigation from menus | Done |
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
| Apps catalog sidebar + **Browse modules** rail entry | Done |
| `res.users` on Laravel `users` + bootstrap admin env | Done |
| ACL admin UI (users, groups, model access, rules) | Done |
| O2M / M2M record dialog + `?embed=1` form save bar | Done |
| `demo_relations` skeleton addon + website docs | Done |

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

## Next up (not done)

| Item | Notes |
|------|--------|
| Kanban / graph / pivot / dashboard views | Arch types exist; no Livewire renderers yet |
| O2M inline / table widget | Dialog mode shipped; inline embed partial |
| Arch-declared `header_actions` / `page_actions` → UI | Tier-2 actions in PLAN; CRUD bar only today |
| Domain OR-groups + computed fields | ORM parity |
| `velmphp/skeleton` separate repo + Packagist split releases | Monorepo path repos work for dev |
| Optional Filament arch adapter | Superseded by velm-ui for MVP |

See [PLAN.md](./PLAN.md) for the long-range design; user-facing behavior is in `website/docs/guides/`.
