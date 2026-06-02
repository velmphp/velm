# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR** (see [CONTEXT.md](./CONTEXT.md) for branch/PR naming).

## Phase 0 — Module runtime

| Item | Status |
|------|--------|
| `__velm__.php` manifest reader + `ModuleSpec` | Done |
| Discovery + topological sort | Done |
| `ir.module` table + repository | Done |
| `php velm module:list` / `module:install` / `module:sync` / `migrate` | Done (DB commands need Laravel bootstrap) |
| Bundled `base` + `admin` manifests | Done |
| Apps catalog UI (Filament `/velm/apps`) | Done |
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
| Arch → Filament schema bridge (list + form) | Done |
| `VelmManager` + `Environment` container binding | Done |
| Filament panel + arch list/create/edit pages | Done |
| `res.company` on base module + default company on install | Done |

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

## Phase 2+

| Item | Status |
|------|--------|
| Model `$inherit` (field extensions, additive schema) | Done |
| ACL / record rules | Planned |
| Full schema migrations (`db:diff`, versioned scripts) | Planned |

See [PLAN.md](./PLAN.md) for remaining ORM parity.
