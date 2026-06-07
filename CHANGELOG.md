# Changelog

All notable changes to the Velm monorepo are documented here. Packagist packages (`velmphp/*`) share releases tagged from this repository.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0-rc3] - 2026-06-05

Last release candidate before stable **1.0.0** — full Tier 2 parity, analytics views, geo reference data, and attachment field widgets.

### Added

- **Computed fields** — `@depends` / `depends()` with stored and unstored compute paths; list/form read includes computed values
- **Domain OR-groups** — `\|`, `&`, `!` prefix notation for search domains and record rules
- **`$mixins`** — abstract model registration (e.g. `mail.thread` via `$mixins = ['mail.thread']`)
- **O2M inline/table widgets** — opt-in `widget('inline')` / `widget('table')` on One2many fields
- **`file` / `files` widgets** — M2O/M2M `ir.attachment` pickers wired to the file library (`file_url` remains for Char URLs)
- **`velm:module:uninstall --drop-schema`** — optional dev-only schema cleanup on uninstall
- **Analytics views** — `KanbanView`, `GraphView`, `PivotView` arch types; `Recordset::readGroup()`; stored routes and ApexCharts graph/pivot pages
- **`geo_data` module** — continents, countries, states, cities; `res.country` on base; partner country relations
- **Production ops guide** — cron, attachments disk, MySQL/Postgres notes (`website/docs/guides/production.md`)
- **CI DB matrix** — MySQL and Postgres dialect smoke jobs alongside SQLite

### Fixed

- **Extended model fields in UI** — list/form/M2O resolution uses registry `fieldSet()` so `$inherit` extensions (e.g. `country_id` on `partners_ext`) render correctly
- **O2M inverse writes** — `Recordset::writeO2m` uses merged field set for comodel columns
- **List toolbar z-index** — search dropdown no longer covers shell menu dropdowns
- **Multi file picker** — “Use selected” closes parent dialog from iframe context

### Install

```bash
composer create-project velmphp/app my_app v1.0.0-rc3 -s rc
cd my_app && composer run setup
```

Sign in at `/velm` with `admin@velm.test` / `password`.

## [1.0.0-rc2] - 2026-06-05

Packagist-installable RC2 — fixes RC1 publishing issues, MIT license, and documented install flags.

### Fixed

- **Packagist RC indexing** — removed `"version"` from library `composer.json` files (tag `v1.0.0-rc1` conflicted with `"version": "1.0.0"`)
- **`create-project` stability** — RC installs require `-s rc` or an explicit `v1.0.0-rc2` pin until stable `1.0.0`
- **Monorepo lock leak** — dropped path-repo `composer.lock` from `velmphp/app`; dependencies resolve from Packagist after RC tags index (run [`scripts/regenerate-app-lock.sh`](./scripts/regenerate-app-lock.sh) post-tag for a pinned lock)

### Changed

- **License:** LGPL-3.0-or-later → **MIT** (all `velmphp/*` packages and `velmphp/app`)
- Library inter-package constraints: `^1.0@dev` (`prefer-stable` picks RC tags on Packagist; path-repo monorepo dev uses `dev-main`)

### Install

```bash
composer create-project velmphp/app my_app v1.0.0-rc2 -s rc
cd my_app && composer run setup
```

Sign in at `/velm` with `admin@velm.test` / `password`.

## [1.0.0-rc1] - 2026-06-05

First public release candidate — installable from Packagist, documented dev reset, and split mirror publishing.

### Added

- **`velmphp/app`** — minimal greenfield Laravel host (`composer create-project velmphp/app`)
- **`velm:migrate:fresh`** — drop Velm schema and reinstall bootstrap modules
- **`velm:seed`** — module-scoped seeders via manifest `SEEDERS`
- Runtime autoloading for `addons/` (no per-module Composer PSR-4)
- Model auto-discovery from `models/` with topological registration order
- Packagist mirror split workflow (`splitsh-lite` → nine `velmphp/*` repos)
- Monorepo **`apps/demo`** reference app with demo addons (`demo_relations`, `change_management`, …)

### Changed

- Published app template split from demo: **`apps/app`** (Packagist) vs **`apps/demo`** (monorepo only)
- Inter-package constraints: `^1.0@RC`
- App lock files pinned for PHP 8.3 (Symfony 7.4)

### Install

```bash
composer create-project velmphp/app my_app -s rc
cd my_app && composer run setup
```

Sign in at `/velm` with `admin@velm.test` / `password`.

[1.0.0-rc3]: https://github.com/velmphp/velm/releases/tag/v1.0.0-rc3
[1.0.0-rc2]: https://github.com/velmphp/velm/releases/tag/v1.0.0-rc2
[1.0.0-rc1]: https://github.com/velmphp/velm/releases/tag/v1.0.0-rc1
