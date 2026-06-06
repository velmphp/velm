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

See **[RELEASE.md](./RELEASE.md)** for the full RC1 runbook.

Tag from `main` (all packages share one semver):

```bash
git tag -a v1.0.0-rc1 -m "Velm 1.0 release candidate 1"
git push origin v1.0.0-rc1
```

The split workflow runs on `v*` tags and pushes the tag to every mirror. Packagist lists version **`1.0.0-RC1`**.

Pushes to **`main`** also re-sync mirrors (for `dev-main` / `1.x-dev` installs).

## Constraints

- **Published installs:** `velmphp/app` requires `velmphp/framework` at `^1.0@dev` with `"prefer-stable": true` (RC tags win over `dev-main`). Use `create-project … -s rc` until stable `1.0.0`. Library packages also use `^1.0@dev`.
- **After stable 1.0.0:** tighten to `^1.0` across packages and `apps/app`.
- **Monorepo dev:** path repos + `^1.0@dev`; `apps/app` optional `composer.local.json` overrides framework to `@dev` for path installs.

## Smoke test after tag

```bash
rm -rf /tmp/velm-smoke
composer create-project velmphp/app /tmp/velm-smoke v1.0.0-rc2 -s rc
cd /tmp/velm-smoke && composer run setup
```

`-s rc` is required until stable **`v1.0.0`** exists (`create-project` defaults to stable-only).

Verify every mirror package lists **`1.0.0-RC2`** on Packagist (not just `dev-main`):

```bash
curl -s https://repo.packagist.org/p2/velmphp/framework.json | python3 -c \
  "import sys,json; print([p['version'] for p in json.load(sys.stdin)['packages']['velmphp/framework']])"
```

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Could not find package velmphp/app with stability stable` | RC is not stable — use `-s rc` or pin `v1.0.0-rc2` (see smoke test above) |
| Packagist shows only `dev-main`, not `1.0.0-RC*` for library packages | Remove `"version"` from `composer.json` in packages (Packagist derives version from the tag; `"version": "1.0.0"` on tag `v1.0.0-rc1` is rejected). Re-tag after fix (`v1.0.0-rc2`) |
| `Source path "../../packages/core" is not found` | `apps/app/composer.lock` was built with monorepo path repos — regenerate lock from Packagist-only install after RC tags index |
| Packagist shows `velmphp/velm-dev` | You submitted the monorepo URL — use mirror URLs instead |
| Split workflow skipped | Add `PACKAGIST_SPLIT_TOKEN` secret |
| Split push 403 `denied to github-actions[bot]` | Checkout was using `GITHUB_TOKEN` — fixed with `persist-credentials: false`; ensure `PACKAGIST_SPLIT_TOKEN` is a PAT with write on mirrors |
| Split push 403 (other) | PAT needs `repo` scope / Contents write on all mirror repos |
| `src refspec main does not match` (danharrin action) | Use splitsh-lite workflow — empty mirrors need direct SHA push |
| `create-project` fails on PHP 8.3 | App lock uses `config.platform.php` 8.3.31 (Symfony 7.4) |
