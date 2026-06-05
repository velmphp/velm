# Packagist release checklist (v1.0-rc1)

Packages to publish under the **velmphp** org:

| Package | Path | Notes |
|---------|------|--------|
| `velmphp/core` | `packages/core` | |
| `velmphp/views` | `packages/views` | |
| `velmphp/modules` | `packages/modules` | Ships bundled `modules/` tree |
| `velmphp/console` | `packages/console` | |
| `velmphp/web` | `packages/web` | |
| `velmphp/ui` | `packages/ui` | Prebuilt CSS/JS in `resources/css/` |
| `velmphp/admin` | `packages/admin` | |
| `velmphp/framework` | `packages/framework` | Metapackage |
| `velmphp/app` | `apps/app` | `create-project` template (minimal) |

**Not on Packagist:** `apps/demo` (`velmphp/velm-demo`) — monorepo reference app with demo addons.

## Before tagging

1. **Constraints** — all inter-package deps use `^1.0@dev` with `"minimum-stability": "dev"` until RC tags land; then tighten to `^1.0` / `^1.0@RC`.
2. **Version field** — each library has `"version": "1.0.0"` for path-repo dev; Packagist uses git tags (prefer `v1.0.0-rc1` on `main`).
3. **App template** — `apps/app/composer.json` has no `repositories`; monorepo dev copies `composer.local.json.example` → `composer.local.json` (merged via `wikimedia/composer-merge-plugin`).
4. **Smoke tests** — CI `app-install` (minimal bootstrap) and `demo-setup` (full reference modules).

## Tagging (suggested)

Tag all packages from the same commit on `main`:

```bash
git tag -a v1.0.0-rc1 -m "Velm 1.0 release candidate 1"
git push origin v1.0.0-rc1
```

Configure each Packagist package to track the tag (split repos or monorepo subtree per package — org preference).

## After Packagist is live

```bash
composer create-project velmphp/app /tmp/velm-smoke
cd /tmp/velm-smoke && composer run setup
```

Update `minimum-stability` / constraints in docs when moving from `@dev` to stable.
