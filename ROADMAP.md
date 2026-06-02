# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR**.

## Phase 0 — Module runtime

| Item | Status |
|------|--------|
| `__velm__.php` manifest reader + `ModuleSpec` | Done |
| Discovery + topological sort | Done |
| `ir.module` table + repository | Done |
| `php velm module:list` / `module:install` / `module:sync` / `migrate` | Done (DB commands need Laravel bootstrap) |
| Bundled `base` + `admin` manifests | Done |
| Apps catalog UI | Planned |
| DATA / VIEW sync on install | Phase 3 |

## Phase 1 — ORM foundation (in progress)

| Item | Status |
|------|--------|
| `Environment`, `Registry`, `RecordCache` | Done |
| Field types: Char, Integer, Boolean, Many2one | Done |
| `Model` + `Recordset` (`create` / `read` / `write` / `search`) | Done |
| PDO SQLite adapter + schema builder (tests) | Done |
| `partners` addon module | Planned |
| Model registration on module install | Planned |
| Laravel DB connection + Filament host | Planned |

## Phase 2+

See [PLAN.md](./PLAN.md) phased delivery.
