# Velm

**Odoo's semantics. Laravel's ergonomics. Filament's craft.**

PHP ERP framework on Laravel + Livewire + Filament. Port of [PyVelm](https://github.com/coolsam726/pyvelm) semantics with a custom module runtime, recordset ORM, and arch-driven views.

- **Organization:** [github.com/velmphp](https://github.com/velmphp)
- **Composer vendor:** `velmphp/*`
- **PHP namespace:** `Velm\`

## Architecture plan

The full feasibility and implementation plan lives in **[PLAN.md](./PLAN.md)** — read this first for module system, ORM, views, migrations, CLI, packages, and phased delivery.

Current progress: **[ROADMAP.md](./ROADMAP.md)**.

## Monorepo packages

| Package | Path |
|---------|------|
| `velmphp/core` | `packages/core` |
| `velmphp/views` | `packages/views` |
| `velmphp/modules` | `packages/modules` |
| `velmphp/console` | `packages/console` |
| `velmphp/web` | `packages/web` |
| `velmphp/ui` | `packages/ui` |
| `velmphp/filament` | `packages/filament` |
| `velmphp/framework` | `packages/framework` |

Bundled Velm modules (`base`, `admin`, …) ship under `packages/modules/modules/`.

## Development

Requires **PHP 8.3+**. Tests use [Pest](https://pestphp.com/) 4.

```bash
composer install
composer test
```

## Status

**Scaffold only** — packages contain stubs; implementation follows [PLAN.md](./PLAN.md) phases.

## License

LGPL-3.0-or-later. See [LICENSE](./LICENSE).
