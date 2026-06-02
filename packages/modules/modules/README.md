# Bundled Velm modules

Each subdirectory is an installable module (same layout as app `addons/{name}/`).

```
modules/
  base/
    __velm__.php      # Manifest::make(...) — module metadata and MODELS list
    models/           # PHP model classes (optional)
  partners/
    __velm__.php
    models/
  admin/
    __velm__.php
```

## Autoloading

Composer maps each module’s models namespace to its `models/` folder, e.g. `Velm\Modules\Partners\Models\` → `modules/partners/models/`.

The package **runtime** (discovery, `ir.module`, install) lives in `../src/` under `Velm\Modules\` — not inside `modules/`.

## App addons

Apps use the same on-disk shape under `addons/{name}/` (see [PLAN.md](../../PLAN.md)); only the Composer PSR-4 prefix differs (`Addons\` vs `Velm\Modules\` for bundled copies).
