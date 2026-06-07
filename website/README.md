# Velm documentation site

Docusaurus site for end-user and module-author documentation.

Branding matches [velmphp/docs](https://github.com/velmphp/docs) (VitePress): Poppins / JetBrains Mono, purple–cyan–indigo palette, and light/dark banner logos in `static/img/`.

## Local preview

```bash
cd website
npm install
npm start
```

Open http://localhost:3000 — marketing landing at `/`, documentation under `/docs/`.

## Build static site

```bash
npm run build
npm run serve
```

Built output is in `website/build/`.

## Versioned and multilingual docs

- **Versions:** Docusaurus snapshots per release (`website/versioned_docs/`). Default: latest tag; **Next** = `main`.
- **Locales:** `en` (default), `fr` (scaffold). UI strings in `website/i18n/`.

Maintainer runbook: [DOCS_MAINTAINERS.md](./DOCS_MAINTAINERS.md)

```bash
npm run docs:version -- 1.0.0   # after each release tag
npm run write-translations -- --locale fr
npm start -- --locale fr
```

## Deployment (GitHub Pages)

Pushes to `main` that touch `website/` trigger [.github/workflows/deploy-docs.yml](../.github/workflows/deploy-docs.yml).

**Live site:** [https://velmphp.github.io/velm/](https://velmphp.github.io/velm/)

### One-time setup (required)

The deploy job fails with `HttpError: Not Found` until Pages is turned on for this repository.

1. Open **[velmphp/velm → Settings → Pages](https://github.com/velmphp/velm/settings/pages)**.
2. Under **Build and deployment**, set **Source** to **GitHub Actions** (not “Deploy from a branch”).
3. If GitHub offers workflow templates, you can dismiss them — this repo already has `deploy-docs.yml`.
4. Re-run the failed workflow: **Actions → Deploy documentation → Re-run all jobs**.

For the `velmphp` organization, an owner may also need **Organization settings → Pages** to allow Pages for this repo.

After the first successful deploy, the `github-pages` environment is created automatically.

Pull requests run the same build (with `baseUrl` `/velm/`) but do not deploy.

Local preview uses `baseUrl` `/`. To mimic production:

```bash
DOCUSAURUS_URL=https://velmphp.github.io DOCUSAURUS_BASE_URL=/velm/ npm run build
npm run serve
```
