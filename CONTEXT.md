# Velm — agent & contributor context

Start with **[PLAN.md](./PLAN.md)** for the full architecture (ORM, modules, views, migrations, CLI, packages).

PyVelm reference implementation: `/home/smaosa/project-pyvelm` (or https://github.com/coolsam726/pyvelm).

## Package dependency order

`core` → `views` → `modules` → (`console` | `web` → `ui` → `filament`) → `framework`

## Key conventions

- Composer vendor: `velmphp/*`
- PHP namespace: `Velm\`
- Module manifests: `__velm__.php`
- Module install state: `ir.module` (not Composer)
- CLI: `php velm …` (Artisan-style colon commands)
- Cron: Laravel Scheduler + `velm:cron:run`
