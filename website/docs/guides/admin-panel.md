---
sidebar_position: 4
---

# Admin panel

The Velm shell is a Livewire panel at `/velm` (configurable via `velm.panel_path`). It renders arch-driven list and form pages, an apps catalog, and company-scoped branding — aligned with PyVelm’s web UI patterns.

## Sign-in and users

Panel login uses Laravel’s session guard. Velm’s security principal **`res.users`** is backed by the same **`users`** table as Laravel (not a separate `res_users` table).

| Setting | Purpose |
|---------|---------|
| `VELM_ADMIN_EMAIL` | Bootstrap admin email (default `admin@velm.test`) |
| `VELM_ADMIN_PASSWORD` | Bootstrap password (default `password`) |
| `config/velm.php` → `bootstrap_admin` | Same values for install/seed |

After `composer run setup`, sign in with those credentials. Velm ACL fields (`group_ids`, `company_id`, etc.) live on `res.users` and are provisioned when the user logs in.

## Navigation layouts

`VELM_MENU_LAYOUT` in `.env` (default `apps`) controls the shell chrome:

| Value | Behavior |
|-------|----------|
| `apps` *(default)* | **Apps rail** on the left: flat list of installed module roots (installed apps first, **Apps** link last) + **secondary top bar** for the active app’s menus |
| `sidebar` | Classic nested sidebar (accordion groups) |

Per-company override: set **Navigation layout** on **Settings → Companies** (`res.company.menu_layout`).

### Apps catalog (`/velm/apps`)

The panel home is the **dashboard** (`/velm` → `/velm/dashboard`). The apps catalog lives at `/velm/apps` and uses the **standard module shell** (same sidebar as workspace pages):

- **Catalog** — browse all discovered modules
- **Status** — filter: All, Installed, Upgrade, Sync pending, Not installed
- **Category** — filter by module category
- **Open app** — jump to an installed module’s first menu entry

From any **module workspace** page (e.g. Partners list), the left rail includes **Apps** as a normal Level 2 link (no section wrapper) to open the catalog.

Module **detail** pages inside the catalog (`/velm/apps/{name}`) hide Status/Category filters and show module-specific actions.

### Install, upgrade, sync, and uninstall

| Action | When to use |
|--------|-------------|
| **Install** | Module is not in `ir.module` yet (first install + dependencies). |
| **Upgrade** | Manifest **version** increased — runs versioned migration scripts, then schema and view/menu sync. |
| **Sync** | Installed module with **schema changes pending** (e.g. new model fields, no version bump) or **views/menus on disk** that differ from the database (no version bump). |
| **Uninstall** | Remove install state and module views/menus (tables/data remain). Blocked when other installed modules depend on or extend this module, or when the module is protected (`base`, `admin`). |

After **Sync** or **Upgrade**, the catalog state returns to **Installed** (including when the only pending change was a removed or renamed view/menu on disk). CLI equivalents: `php artisan velm:module:install`, `velm:migrate` / reconcile, `velm:module:sync`, and `velm:module:uninstall`.

**Schema drift** (e.g. unsupported SQLite alters) may be shown on a module card for information; it does not change the actionable **Sync pending** state. See [Platform features](./features#module-states-in-the-catalog).

Install modules from the catalog grid; see [Installation](./installation).

## Company branding

White-label settings live on **`res.company`** (form section **Branding & white-label**) and can be overridden per environment.

| Field / env | Effect |
|-------------|--------|
| **Application name** (`app_name`) | Header title and login page (preferred over company **Name**) |
| `VELM_APP_NAME` | Fallback when `app_name` is empty |
| `APP_NAME` | Laravel default; used only if nothing else is set |
| `logo_url` / `logo_url_dark` | Header logos (light/dark); use **Browse…** on the company form to pick from the file library |
| `VELM_LOGO_URL` / `VELM_LOGO_URL_DARK` | Env overrides for logos |
| Dark logo fallback | When `logo_url_dark` is empty, the light logo is shown in dark mode |
| `primary_color` | Theme accent CSS |
| `header_logo_height` | Logo height in the header (px) |
| `show_header_brand_text` | Show or hide the app name beside the logo |
| `favicon_url`, `copyright_text`, `support_email`, `support_url` | Optional extras |
| **Timezone** (`timezone`) | UTC storage for datetimes; panel and API display/input in this zone |

Configure env keys under `config/velm.php` → `branding` (e.g. `VELM_APP_NAME=My ERP`).

**Dark mode** follows `velm-tokens.css`. Rebuild after theme or shell changes:

```bash
# Monorepo root — Tailwind CSS + Flowbite JS
composer build-ui

# Skeleton — build and publish assets to public/
cd apps/skeleton && composer velm-build-css
```

Clicking the **brand mark** in the header goes to the apps catalog (`/velm/apps`).

## File manager

Install the **`file_manager`** module from the apps catalog. The shell adds **Files** in the module rail:

| Menu | Purpose |
|------|---------|
| **Library** | Drive-style UI at `/web/files/library` |
| **All files** | Read-only list of `ir.attachment` records |
| **Folders** | Manage `res.attachment.folder` |

Company branding forms use the same library via the **`file_url`** widget (logos, favicon). See [Platform features — File manager](./features#file-manager-and-attachments).

## Company switcher

Users with access to multiple companies can switch the active company from the shell (cookie-backed). Record rules and `company_id` on create respect the active company. Datetimes use the active company’s **Timezone** (see [Platform features](./features#utc-storage-and-company-timezone)).

## Settings (admin module)

After installing **`admin`**, the shell exposes:

| Menu area | Models |
|-----------|--------|
| Users & groups | `res.users`, `res.groups` |
| Access control | `ir.model.access`, `ir.rule` |

`res.users` uses Laravel’s `users` table; configure bootstrap credentials via `VELM_ADMIN_EMAIL` / `VELM_ADMIN_PASSWORD`.

## Rebuild UI assets

If the panel looks unstyled or colors are wrong:

```bash
# From monorepo root (packages/ui only)
composer build-ui

# From skeleton (build + vendor:publish velm-ui-assets)
cd apps/skeleton
composer velm-build-css
```

This runs `npm run build` in `packages/ui` (Tailwind CSS and Flowbite JS) and, in the skeleton, copies assets to `public/css/velm/` and `public/js/velm/`.

## HTTP surfaces

| URL | Purpose |
|-----|---------|
| `/velm` | Panel (redirects to apps catalog) |
| `/velm/apps` | Module catalog |
| `/velm/views/{module}/{viewName}` | Arch-driven list pages |
| `/velm/views/{module}/{viewName}/{id}` | Record display |
| `/velm/views/{module}/{viewName}/{id}/edit` | Record edit |
| `/velm/views/{module}/{viewName}/create` | Create (editable form; uses `formView`, not `detailView`) |
| `/web/files/library` | File library (requires `file_manager` module) |
| `/web/files/picker` | File picker (embed / dialog) |

The **New** list button targets `/create`. Create routes are registered before the generic `/{record}` detail route so `create` is not parsed as a record id.

JSON APIs: see [Models overview](../models/index.md#related-apis) and [Platform features](./features#http-json-api).

## See also

- [Platform features](./features) — full feature index
- [Views and forms](./views-and-forms) — list toolbar, form layout, relational widgets
- [Security](../models/security) — `perm_unlink` and row-level rules
- [Installation](./installation) — setup and modules
