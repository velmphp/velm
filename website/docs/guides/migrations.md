---
sidebar_position: 4
---

# Module migrations

Velm uses a **two-layer** migration model (same as PyVelm/Odoo):

1. **Model-driven schema diff** — on install, upgrade, and `module:sync`, declared fields are compared to the live database. New tables and columns are applied automatically.
2. **Versioned scripts** — when `ir.module.version` is behind the manifest `VERSION`, PHP files in `migrations/` run before the diff.

## CLI

```bash
php velm migrate --module=partners   # install or upgrade (deps first)
php velm migrate                     # bootstrap modules
php velm module:sync partners        # views, menus, schema diff (no version bump)
php velm db:diff --module=partners   # show drift without applying
php velm db:autogen --module=partners  # scaffold migration + minor VERSION bump
php velm db:status                   # installed vs manifest versions
```

## SYNC_HOOK

Declare an idempotent backfill that runs **before** schema apply on every install, upgrade, `migrate`, and `module:sync`:

```php
return Manifest::make('partners')
    ->version(0, 1, 0)
    ->syncHook(PartnersHooks::class); // calls PartnersHooks::sync(Environment $env)
```

Or in a plain array manifest: `'SYNC_HOOK' => PartnersHooks::class.'::sync'`.

Use this to backfill required columns before `SET NOT NULL` can apply, or to drop orphan columns safely.

## Versioned migration files

Place scripts under `your_module/migrations/` using the naming convention:

```text
0_1_0_to_0_2_0.php   # bridges manifest 0.1.0 → 0.2.0
```

Each file must **return a callable** that accepts `Velm\Environment`:

```php
<?php

use Velm\Environment;

return static function (Environment $env): void {
    // Idempotent data backfill or manual DDL
};
```

Scripts run in filename order when the installed version is **below** the target manifest version.

## What auto-applies vs manual

| Change | Auto |
|--------|------|
| New table / column | Yes |
| Drop NOT NULL when model is optional | Yes (Postgres) |
| SET NOT NULL when no NULL rows | Yes (Postgres) |
| SET NOT NULL with NULL rows | No — backfill in `SYNC_HOOK` or a migration first |
| Column type change / rename | No — use a versioned script |
| Orphan column (removed from model) | No — `SYNC_HOOK` or manual DROP |

See [PLAN.md](https://github.com/velmphp/velm/blob/main/PLAN.md) for the full parity checklist.
