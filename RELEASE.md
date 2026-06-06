# Release v1.0.0-rc1

Runbook for maintainers after [PACKAGIST.md](./PACKAGIST.md) one-time setup is complete (mirrors, split token, Packagist linked).

## Pre-flight

- [ ] `main` CI green (tests + `app-install` + `demo-setup`)
- [ ] RC prep PR merged (`^1.0@RC` constraints, CHANGELOG)
- [ ] All nine mirror repos populated on `main`
- [ ] All nine packages visible on Packagist under `velmphp/*`

## Tag and publish

From an up-to-date `main`:

```bash
git pull origin main
git tag -a v1.0.0-rc1 -m "Velm 1.0 release candidate 1"
git push origin v1.0.0-rc1
```

This triggers **Split packages to mirror repos**, which pushes `v1.0.0-rc1` to every mirror. Packagist webhooks should update within a few minutes.

## Verify mirrors

Each mirror should show tag `v1.0.0-rc1`:

- https://github.com/velmphp/core/releases
- https://github.com/velmphp/framework/releases
- … (all nine)

On Packagist, each package should list version **`1.0.0-RC1`** (Composer normalizes `v1.0.0-rc1`).

## Smoke test (clean machine or `/tmp`)

```bash
rm -rf /tmp/velm-rc1-smoke
composer create-project velmphp/app /tmp/velm-rc1-smoke
cd /tmp/velm-rc1-smoke
composer run setup
php artisan serve
```

Open http://127.0.0.1:8000/velm — sign in with `admin@velm.test` / `password`.

Optional: pin exact RC during smoke test:

```bash
composer create-project velmphp/app /tmp/velm-rc1-smoke v1.0.0-rc1
```

## GitHub release (optional)

Create a release on `velmphp/velm` from tag `v1.0.0-rc1`. Copy the `[1.0.0-rc1]` section from [CHANGELOG.md](./CHANGELOG.md).

## After RC1

| When | Action |
|------|--------|
| Bugfix on RC | Tag `v1.0.0-rc2`, same flow |
| Stable 1.0 | Tag `v1.0.0`, tighten constraints to `^1.0`, update ROADMAP 1.3 → Done |
| Docs | Bump ROADMAP / intro if needed |

## Rollback

Delete the tag locally and remotely (only if RC was not widely consumed):

```bash
git tag -d v1.0.0-rc1
git push origin :refs/tags/v1.0.0-rc1
```

Re-run split from `main` to refresh mirrors.
