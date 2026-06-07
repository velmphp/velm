# Release v1.0.0 (stable)

Runbook for maintainers after [PACKAGIST.md](./PACKAGIST.md) one-time setup is complete (mirrors, split token, Packagist linked).

## Pre-flight

- [ ] `main` CI green (tests + coverage + `app-install` + `demo-setup` + DB matrix)
- [ ] Stable prep merged: `^1.0` constraints, [CHANGELOG](./CHANGELOG.md) `[1.0.0]`, docs snapshot `1.0.0`
- [ ] All nine mirror repos populated on `main`
- [ ] All nine packages visible on Packagist under `velmphp/*`

## Tag and publish

From an up-to-date `main`:

```bash
git pull origin main
git tag -a v1.0.0 -m "Velm 1.0.0 stable"
git push origin v1.0.0
```

This triggers **Split packages to mirror repos**, which pushes `v1.0.0` to every mirror. Packagist webhooks should update within a few minutes.

## Verify Packagist

Each package should list **`1.0.0`**:

```bash
for pkg in core views modules console web ui admin framework app; do
  echo -n "velmphp/$pkg: "
  curl -s "https://repo.packagist.org/p2/velmphp/$pkg.json" | python3 -c \
    "import sys,json; print([p.get('version') for p in json.load(sys.stdin)['packages'].get('velmphp/$pkg',[])])"
done
```

If library packages show only `dev-main`, Packagist rejected the tag — confirm library `composer.json` files have **no** `"version"` field and re-tag.

## Regenerate app lock (recommended)

After all nine packages show `1.0.0`:

```bash
./scripts/regenerate-app-lock.sh
git add apps/app/composer.lock
git commit -m "Pin velmphp/app lock from Packagist 1.0.0"
git push origin main
```

## Snapshot documentation

```bash
cd website && npm ci
npm run docs:version -- 1.0.0
npm run write-translations -- --locale fr
```

Commit `versioned_docs/`, `versions.json`, `docusaurus.config.ts`, and i18n updates; push `main` (see [website/DOCS_MAINTAINERS.md](./website/DOCS_MAINTAINERS.md)).

## Smoke test

```bash
rm -rf /tmp/velm-stable-smoke
composer create-project velmphp/app /tmp/velm-stable-smoke
cd /tmp/velm-stable-smoke
composer run setup
php artisan serve
```

Open http://127.0.0.1:8000/velm — sign in with `admin@velm.test` / `password`.

No `-s rc` flag is required for stable installs.

## Local test coverage

Install **pcov** for your PHP version (e.g. `apt install php8.3-pcov`), then from the monorepo root:

```bash
composer test:coverage:report
```

This writes `coverage.xml` and fails if line coverage drops below the configured minimum (currently **95%**; see [phpunit.xml](phpunit.xml) scope).

## GitHub release

Create a **release** (not pre-release) on `velmphp/velm` from tag `v1.0.0`. Copy the `[1.0.0]` section from [CHANGELOG.md](./CHANGELOG.md).

## Post-1.0 patches

| When | Action |
|------|--------|
| Bugfix | Tag `v1.0.1`, same flow |
| Minor feature | Tag `v1.1.0` per [ROADMAP.md](./ROADMAP.md) Tier 3 |
| Docs only | Update `website/docs/` + deploy; optional `docs:version` on next tag |

## Rollback

Delete the tag locally and remotely (only if the release was not widely consumed):

```bash
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0
```

Re-run split from `main` to refresh mirrors.
