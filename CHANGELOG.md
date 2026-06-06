# Changelog

All notable changes to the Velm monorepo are documented here. Packagist packages (`velmphp/*`) share releases tagged from this repository.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
composer create-project velmphp/app my_app
cd my_app && composer run setup
```

Sign in at `/velm` with `admin@velm.test` / `password`.

[1.0.0-rc1]: https://github.com/velmphp/velm/releases/tag/v1.0.0-rc1
