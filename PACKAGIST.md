# Packagist release checklist (v1.0-rc1)

Public [Packagist.org](https://packagist.org) **cannot** install packages from monorepo subdirectories. It only reads `composer.json` at the **repository root** — which is why submitting `https://github.com/velmphp/velm` offers **`velmphp/velm-dev`** (the dev workspace). **Do not publish that package.**

Instead, this monorepo splits each publishable tree into **mirror repos** via GitHub Actions (`.github/workflows/split-packages.yml`). Packagist tracks the mirrors.

## Package map

| Composer name | Monorepo path | Mirror GitHub repo |
|---------------|---------------|-------------------|
| `velmphp/core` | `packages/core` | [velmphp/core](https://github.com/velmphp/core) |
| `velmphp/views` | `packages/views` | [velmphp/views](https://github.com/velmphp/views) |
| `velmphp/modules` | `packages/modules` | [velmphp/modules](https://github.com/velmphp/modules) |
| `velmphp/console` | `packages/console` | [velmphp/console](https://github.com/velmphp/console) |
| `velmphp/web` | `packages/web` | [velmphp/web](https://github.com/velmphp/web) |
| `velmphp/ui` | `packages/ui` | [velmphp/ui](https://github.com/velmphp/ui) |
| `velmphp/admin` | `packages/admin` | [velmphp/admin](https://github.com/velmphp/admin) |
| `velmphp/framework` | `packages/framework` | [velmphp/framework](https://github.com/velmphp/framework) |
| `velmphp/app` | `apps/app` | [velmphp/app](https://github.com/velmphp/app) |

**Not published:** `velmphp/velm-dev` (monorepo root), `velmphp/velm-demo` (`apps/demo`).

## One-time setup

### 1. Create empty mirror repositories

Under the **velmphp** GitHub org, create **nine empty public repos** (no README, no `.gitignore` — completely empty is fine):

`core`, `views`, `modules`, `console`, `web`, `ui`, `admin`, `framework`, `app`

The split workflow uses **splitsh-lite** and pushes directly to `main` on each mirror, so the first run bootstraps empty repos.

### 2. Add split token secret

Create a **classic PAT** (`repo` scope) or **fine-grained PAT** with **Contents: Read and write** on all nine mirror repos (and ideally `velmphp/velm`). Use a personal account or bot user that has **write access** to those repos in the org.

Do **not** reuse the workflow’s default `GITHUB_TOKEN` — it cannot push to other repositories.

In **`velmphp/velm`** → Settings → Secrets and variables → Actions, add:

| Secret | Value |
|--------|--------|
| `PACKAGIST_SPLIT_TOKEN` | The PAT (not `GITHUB_TOKEN`) |

Until this secret exists, the split workflow is skipped (no failed CI).

### 3. Bootstrap mirrors

Actions → **Split packages to mirror repos** → **Run workflow** on `main`.

This pushes each subdirectory to its mirror’s default branch (`main`).

### 4. Register mirrors on Packagist

Log in to Packagist (link the **velmphp** GitHub org). Submit **each mirror URL** separately — **not** the monorepo:

```
https://github.com/velmphp/core
https://github.com/velmphp/views
https://github.com/velmphp/modules
https://github.com/velmphp/console
https://github.com/velmphp/web
https://github.com/velmphp/ui
https://github.com/velmphp/admin
https://github.com/velmphp/framework
https://github.com/velmphp/app
```

Enable the Packagist GitHub hook for auto-updates on push.

Claim the **`velmphp`** vendor on Packagist by becoming maintainer on one package.

## Releasing

Tag from `main` (all packages share one semver):

```bash
git tag -a v1.0.0-rc1 -m "Velm 1.0 release candidate 1"
git push origin v1.0.0-rc1
```

The split workflow runs on `v*` tags and pushes the tag to every mirror. Packagist picks up new versions via webhook.

Pushes to **`main`** also re-sync mirrors (for `dev-main` / `1.x-dev` installs).

## Before tagging

1. **Constraints** — inter-package deps use `^1.0@dev` until RC tags land; then tighten to `^1.0` / `^1.0@RC`.
2. **Version field** — each library has `"version": "1.0.0"` for path-repo dev; Packagist uses git tags on mirrors.
3. **App template** — `apps/app/composer.json` has no path `repositories`; monorepo dev uses `composer.local.json`.
4. **Smoke tests** — CI `app-install` and `demo-setup` in `velmphp/velm`.

## After Packagist is live

```bash
composer create-project velmphp/app /tmp/velm-smoke
cd /tmp/velm-smoke && composer run setup
```

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Packagist shows `velmphp/velm-dev` | You submitted the monorepo URL — use mirror URLs instead |
| Split workflow skipped | Add `PACKAGIST_SPLIT_TOKEN` secret |
| Split push 403 `denied to github-actions[bot]` | Checkout was using `GITHUB_TOKEN` — fixed with `persist-credentials: false`; ensure `PACKAGIST_SPLIT_TOKEN` is a PAT with write on mirrors |
| Split push 403 (other) | PAT needs `repo` scope / Contents write on all mirror repos |
| `src refspec main does not match` (danharrin action) | Use splitsh-lite workflow — empty mirrors need direct SHA push |
| `create-project` fails on PHP 8.3 | App lock uses `config.platform.php` 8.3.31 (Symfony 7.4) |
