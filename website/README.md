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

## Deployment (GitHub Pages)

Pushes to `main` that touch `website/` trigger [.github/workflows/deploy-docs.yml](../.github/workflows/deploy-docs.yml).

**Live site:** [https://velmphp.github.io/velm/](https://velmphp.github.io/velm/)

One-time repo setup:

1. **Settings → Pages → Build and deployment** → Source: **GitHub Actions**.
2. After the first successful run, the site is published from the `github-pages` environment.

Pull requests run the same build (with `baseUrl` `/velm/`) but do not deploy.

Local preview uses `baseUrl` `/`. To mimic production:

```bash
DOCUSAURUS_URL=https://velmphp.github.io DOCUSAURUS_BASE_URL=/velm/ npm run build
npm run serve
```
