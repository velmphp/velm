# Release v1.0.0-rc2

Runbook for maintainers after [PACKAGIST.md](./PACKAGIST.md) one-time setup is complete (mirrors, split token, Packagist linked).

## Pre-flight

- [ ] `main` CI green (tests + `app-install` + `demo-setup`)
- [ ] RC2 prep merged: MIT license, no `"version"` in library `composer.json`, `^1.0@dev` constraints, [CHANGELOG](./CHANGELOG.md) `[1.0.0-rc2]`
- [ ] All nine mirror repos populated on `main`
- [ ] All nine packages visible on Packagist under `velmphp/*`

## Tag and publish

From an up-to-date `main`:

```bash
git pull origin main
git tag -a v1.0.0-rc2 -m "Velm 1.0 release candidate 2"
git push origin v1.0.0-rc2
```

This triggers **Split packages to mirror repos**, which pushes `v1.0.0-rc2` to every mirror. Packagist webhooks should update within a few minutes.

## Verify Packagist

Each package should list **`1.0.0-RC2`** (Composer normalizes `v1.0.0-rc2`):

```bash
for pkg in core views modules console web ui admin framework app; do
  echo -n "velmphp/$pkg: "
  curl -s "https://repo.packagist.org/p2/velmphp/$pkg.json" | python3 -c \
    "import sys,json; print([p.get('version') for p in json.load(sys.stdin)['packages'].get('velmphp/$pkg',[])])"
done
```

If library packages show only `dev-main`, Packagist rejected the tag — confirm library `composer.json` files have **no** `"version"` field and re-tag.

## Regenerate app lock (optional, recommended)

After all nine packages show `1.0.0-RC2`:

```bash
./scripts/regenerate-app-lock.sh
git add apps/app/composer.lock
git commit -m "Pin velmphp/app lock from Packagist RC2"
git push origin main
```

Split on `main` updates the app mirror; users on `dev-main` get the pinned lock. Tagged `v1.0.0-rc2` dist stays without lock unless you tag `v1.0.0-rc2.1` or accept lock on `main` only.

## Smoke test

```bash
rm -rf /tmp/velm-rc2-smoke
composer create-project velmphp/app /tmp/velm-rc2-smoke v1.0.0-rc2 -s rc
cd /tmp/velm-rc2-smoke
composer run setup
php artisan serve
```

Open http://127.0.0.1:8000/velm — sign in with `admin@velm.test` / `password`.

## GitHub release

Create a **pre-release** on `velmphp/velm` from tag `v1.0.0-rc2`. Copy the `[1.0.0-rc2]` section from [CHANGELOG.md](./CHANGELOG.md).

## RC1 post-mortem (reference)

| Issue | Fix in RC2 |
|-------|------------|
| `Could not find package … with stability stable` | Use `-s rc` or pin `v1.0.0-rc2` |
| Library packages missing as RC on Packagist | Removed `"version": "1.0.0"` from library `composer.json` |
| `Source path "../../packages/core" is not found` | Removed monorepo path lock from `velmphp/app` |
| LGPL → MIT | All `composer.json` + [LICENSE](./LICENSE) |

## After RC2

| When | Action |
|------|--------|
| Bugfix on RC | Tag `v1.0.0-rc3`, same flow |
| Stable 1.0 | Tag `v1.0.0`, tighten constraints to `^1.0`, update ROADMAP 1.3 → Done |
| Docs | Bump ROADMAP / intro if needed |

## Rollback

Delete the tag locally and remotely (only if RC was not widely consumed):

```bash
git tag -d v1.0.0-rc2
git push origin :refs/tags/v1.0.0-rc2
```

Re-run split from `main` to refresh mirrors.
