# Documentation site — maintainers

The public docs live in `website/` (Docusaurus 3) and deploy to [https://velmphp.github.io/velm/](https://velmphp.github.io/velm/).

## Layout

| Path | Purpose |
|------|---------|
| `website/docs/` | **Next** — unreleased docs for `main` (`/docs/next/…`) |
| `website/versioned_docs/version-X/` | Frozen snapshot per release tag |
| `website/versions.json` | Ordered list of released doc versions |
| `website/i18n/{locale}/` | UI strings + translated markdown |

Default version for visitors: **latest entry in `versions.json`** (currently `1.0.0`). **Next** shows an “unreleased” banner.

## Cut a docs version (each git tag)

After merging release prep to `main` and before or right after tagging:

```bash
cd website
npm ci
npm run docs:version -- 1.0.0-rc3   # use the semver tag name
```

This copies `docs/` → `versioned_docs/version-1.0.0-rc3/`, updates `versions.json`, and creates sidebar metadata.

Then:

1. Edit `docusaurus.config.ts` — add the new version under `presets…docs.versions` with `banner: 'none'`, set `lastVersion` to the new tag, and update `latestDocsVersion` / `latestDocsBase` at the top of the file.
2. Run `npm run write-translations -- --locale fr` (and other locales) to refresh i18n JSON for the new version label/sidebar keys.
3. Commit `versioned_docs/`, `versioned_sidebars/`, `versions.json`, `docusaurus.config.ts`, and any `i18n/` updates.
4. Push `main` — **Deploy documentation** workflow rebuilds the full site (all versions + locales).

When **1.0.0** ships, snapshot it the same way and optionally mark older RC doc trees with `banner: 'unmaintained'` in config.

## Translations (i18n)

Configured locales: **en** (default), **fr** (scaffold).

| Task | Command |
|------|---------|
| Regenerate UI string catalogs | `npm run write-translations -- --locale fr` |
| Translate a doc page | Copy structure under `i18n/fr/docusaurus-plugin-content-docs/current/` (Next) or `…/version-1.0.0-rc2/` (frozen release) |
| Local preview (French) | `npm run start -- --locale fr` |
| Build all locales | `npm run build` (CI does this automatically) |

Untranslated doc pages fall back to English content. Prefer translating **Next** first; backport to `version-*` folders when cutting a release if needed.

## Local preview

```bash
cd website
npm install
npm start                    # English, default version
npm start -- --locale fr     # French UI + translated pages
DOCUSAURUS_URL=https://velmphp.github.io DOCUSAURUS_BASE_URL=/velm/ npm run build
npm run serve
```

## Deployment

Pushes to `main` that touch `website/` run [.github/workflows/deploy-docs.yml](../.github/workflows/deploy-docs.yml) (GitHub Pages, single artifact with every version and locale).

Pull requests build only — no deploy.

## URL map (production)

| URL | Content |
|-----|---------|
| `/velm/docs/1.0.0-rc2/intro` | English docs for tag `v1.0.0-rc2` |
| `/velm/docs/next/intro` | English docs for `main` |
| `/velm/fr/docs/1.0.0-rc2/intro` | French (translated pages + fallback) |
| `/velm/fr/docs/next/intro` | French Next |
