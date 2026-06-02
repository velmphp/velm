# Velm roadmap

Implementation follows [PLAN.md](./PLAN.md). Work lands via **feature branch → PR**.

## Phase 0 — Module runtime (in progress)

| Item | Status |
|------|--------|
| `__velm__.php` manifest reader + `ModuleSpec` | Done |
| Discovery + topological sort | Done |
| `ir.module` table + repository | Done |
| `php velm module:list` / `module:install` / `module:sync` / `migrate` | Done (DB commands need Laravel bootstrap) |
| Bundled `base` + `admin` manifests | Done |
| Apps catalog UI | Planned |
| DATA / VIEW sync on install | Phase 3 |

## Phase 1 — Foundation

Environment, Registry, BaseModel, `partners` addon, Filament host.

## Phase 2+

See [PLAN.md](./PLAN.md) phased delivery.
