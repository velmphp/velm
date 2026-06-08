---
sidebar_position: 6
---

# Platform features

This page summarizes Velm shell and ORM capabilities added beyond the core model docs. Use it as a map; each section links to deeper guides.

## Automatic timestamps

Every **base** model gets `created_at` and `updated_at` unless you set `protected static bool $timestamps = false` or declare your own datetime fields with those names.

| Behavior | Detail |
|----------|--------|
| **On create** | Both fields set if missing or null |
| **On write** | `updated_at` always refreshed; `created_at` never changed from the client |
| **Schema** | Columns added on module install/sync (`DatetimeField`, SQL `TIMESTAMP`) |
| **Scaffolds** | `velm:make:view` skips timestamp fields in list/form arch by default |

See [Defining models — timestamps](../models/defining-a-model#model-class).

### UTC storage and company timezone

| Layer | Rule |
|-------|------|
| **Database** | All `DatetimeField` values (including automatic timestamps) stored as **UTC** (`Y-m-d H:i:s`) |
| **UI & API** | When a company is active, values are shown and parsed in that company’s **Timezone** (`res.company.timezone`, default `UTC`) |
| **No company** | Superuser “all companies” mode and CLI tests without `timezone` in context use UTC end-to-end |

Set timezone on **Settings → Companies** (e.g. `Europe/Brussels`, `America/New_York`). Invalid identifiers fall back to `UTC`.

The active company’s timezone is bound into `Environment` on each panel/API request (`BindVelmEnvironment`).

## Apps catalog and navigation

| Feature | Description |
|---------|-------------|
| **Default home** | `/velm` redirects to `/velm/dashboard` (ACL-gated widgets from installed modules) |
| **Dashboard** | Composable stat/list widgets; modules register via `{module}/dashboard.php` |
| **Catalog sidebar** | Dedicated filters: Catalog, Status, Category, Open app |
| **Status filters** | All, Installed, **Upgrade**, **Sync pending**, Not installed |
| **Module rail** | Flat list of installed apps (no “Installed” heading); **Apps** link last to return to the catalog |
| **Workspace entry** | From any module page, **Apps** in the left rail opens the catalog |
| **Per-company layout** | `res.company.menu_layout`: `apps` (rail + top bar) or `sidebar` (classic accordion) |
| **Env default** | `VELM_MENU_LAYOUT` in `.env` |

### Module states in the catalog

| State | Meaning | Action |
|-------|---------|--------|
| **Not installed** | Absent from `ir.module` | **Install** |
| **Installed** | Up to date | **Sync**, **Uninstall** (if allowed), **Open app** (if menus exist) |
| **Upgrade** | Installed manifest **version** is newer | **Upgrade** (versioned migrations + sync) |
| **Sync pending** | Same version but **actionable** schema diff (new columns, etc.) **or** views/menus on disk differ from `ir.ui.view` / `ir.ui.menu` | **Sync** |
| **Schema drift** | Unsupported diff (e.g. SQLite nullability-only changes) | Informational only — does not block “Installed”; fix manually or bump version |

**Sync** re-applies schema, reloads views and menus from disk, **removes stale menu entries**, and **removes views** that no longer exist in module data files (so renamed or deleted views do not leave the module stuck on Sync pending).

`schemaExternalColumns()` on a model (e.g. Laravel-owned `users.password`) excludes columns from actionable sync diff so Laravel tables do not show false “sync pending”.

CLI: `php artisan velm:module:install`, `velm:module:sync`, `velm:module:uninstall`, `velm:migrate` / reconcile. See [Admin panel — Install](./admin-panel#install-upgrade-sync-and-uninstall) and [Migrations](./migrations).

### Uninstall

Remove a module from `ir.module` and delete its synced views and menus. **Database tables and row data are kept.**

| Blocker | Meaning |
|---------|---------|
| Protected module | `base`, `admin`, `geo_data`, `file_manager`, or entries in `velm.bootstrap_modules` |
| Reverse dependency | Another **installed** module lists this one in `DEPENDS` |
| Model extension | Another installed module extends this module's models via `$inherit` |

The catalog shows a disabled **Uninstall** button with a short summary (e.g. “The following modules depend on it: …”). Uninstall dependents first.

```bash
php artisan velm:module:uninstall partners_ext
```

## Company branding and switcher

White-label fields on **`res.company`** (section **Branding & white-label**):

- Application name, logos (light/dark), primary color, font, favicon
- **Logo and favicon fields** use the **`file_url` widget** — browse the file library, store `/api/attachment/{id}/download` URLs, and mark picked files **public** for header use
- If **Logo URL (dark)** is empty, the **light logo** is used in dark mode
- Copyright, support email/URL, “powered by Velm” toggle
- Header logo height, show/hide brand text next to logo

Environment overrides: `VELM_APP_NAME`, `VELM_LOGO_URL`, `VELM_LOGO_URL_DARK`, etc. (`config/velm.php` → `branding`).

**Company switcher** in the header (cookie-backed) sets active `company_id` for record rules and default `company_id` on create.

**Dark mode** uses shared design tokens (`packages/ui/resources/css/velm-tokens.css`). Rebuild UI assets after token or Blade changes:

```bash
# Monorepo root (Tailwind CSS + Flowbite JS in packages/ui)
composer build-ui

# Skeleton app (build + publish to public/)
cd apps/demo && composer velm-build-css
```

## File manager and attachments

The **`file_manager`** module adds Drive-style storage over **`ir.attachment`**:

| Surface | URL / API |
|---------|-----------|
| **File library** | `/web/files/library` — folders, grid/tiles/details, upload, bulk actions, properties panel |
| **File picker** | `/web/files/picker` — opened from **`file_url`**, **`file`**, and **`files`** fields via the record dialog |
| **Upload / download / delete** | `POST /api/attachment/upload`, `GET /api/attachment/{id}/download`, `DELETE /api/attachment/{id}` |
| **Web file routes** | `/web/files/*` — tree, move/copy, bulk download/public, picker browse/upload |

Installed with bootstrap modules (`velm:migrate`). Menus: **Files → Library**, **All files**, **Folders**.

Storage backend: `VELM_ATTACHMENT_BACKEND` (`local` or `db`); local path via `VELM_ATTACHMENT_DIR`. See module manifest under `packages/modules/modules/file_manager/`.

### Attachment field widgets (`file`, `files`)

For relational fields pointing at **`ir.attachment`**, use dedicated widgets instead of raw M2O/M2M chips:

| Widget | Field type | UI |
|--------|------------|-----|
| **`file`** | `Many2oneField('ir.attachment')` | Single chip + **Pick a file** |
| **`files`** | `Many2manyField('ir.attachment')` | Multi chip list + multi-select picker |

```php
Field::make('cover_id')->widget('file')->accept('image/*'),
Field::make('document_ids')->widget('files'),
```

Requires **`file_manager`**. **`file_url`** remains for Char columns that store download URLs (e.g. company logos). See [Views and forms — Attachment pickers](./views-and-forms#attachment-pickers-file-files).

## Analytics views (kanban, graph, pivot)

Stored view types beyond list/form/detail:

| View | Authoring | Shell |
|------|-----------|-------|
| **Kanban** | `KanbanView::make('…')->model(…)->groupBy('…')->card(…)` | Column board with card template |
| **Graph** | `GraphView::make('…')->measure(…)->groupBy(…)` | Bar/line chart (ApexCharts) |
| **Pivot** | `PivotView::make('…')->row(…)->col(…)->measure(…)` | Row/column groupby grid |

Data comes from **`Recordset::readGroup()`** (sum, avg, min, max, count) via `/velm/api/analytics/*` endpoints. A view switcher on list pages links sibling stored views when registered.

Demo: **Partners** module ships `partner.kanban`, `partner.graph`, and `partner.pivot` under **Contacts → Partners** (sync `partners` module). Authoring details: [Views and forms](./views-and-forms).

## Users, groups, and ACL in the shell

| Model | Admin menus (module `admin`) |
|-------|------------------------------|
| `res.users` | Users — name, email, password, groups, company |
| `res.groups` | Groups — members (`user_ids` M2M) |
| `ir.model.access` | Model access — per-model CRUD flags |
| `ir.rule` | Record rules — row-level domains |

## System audit (IT audit trail)

The **`system_audit`** module adds an append-only IT audit log for compliance:

| Model | Purpose | Panel menus |
|-------|---------|-------------|
| `ir.audit.log` | CRUD trail for all models — before/after JSON, actor, IP, user agent, company | **Security → Audit → Audit log** |
| `ir.login.log` | Login success/failure/logout events with session lifetime | **Security → Audit → Login history** |
| `ir.user.lifecycle` | User lifecycle events (created, activated/deactivated, password/groups changes) | **Security → Audit → User lifecycle** |

Key behaviors:

- **Append-only** — application users cannot modify or delete audit rows; mutations are only allowed when `Environment::withAclBypass()` is active (used by internal writers and the retention cron).
- **Company scoping** — audit rows carry a `company_id` where applicable (for `res.company` edits this is the company itself) so the shell can filter logs by the active company.
- **Retention** — a daily cron job purges audit rows older than `VELM_AUDIT_RETENTION_DAYS` (default 90 days).

Configuration (see [Production operations](./production#environment-variables)):

- `VELM_AUDIT_DSN` — optional separate database URL for the audit tables (empty = main app DB)
- `VELM_AUDIT_RETENTION_DAYS` — keep audit/login/lifecycle rows for this many days

`res.users` maps to Laravel’s **`users`** table. Bootstrap admin: `VELM_ADMIN_EMAIL` / `VELM_ADMIN_PASSWORD`.

Password handling: empty password on save leaves the hash unchanged; plain text is hashed on write. `password` is listed in `schemaExternalColumns()` so Velm schema sync does not treat it as a missing column.

Panel login uses Laravel session guard; Velm ACL applies after bind. See [Security](../models/security).

## List views

| Feature | API / behavior |
|---------|----------------|
| **Click to open** | `->clickToOpen()` + `->detailView('…')` |
| **Row actions** | `ListRowAction::open()`, `::edit()`, `::delete()` |
| **ACL gating** | Open/read, Edit/write, Delete/unlink; delete auto-added when `perm_unlink` |
| **Inline boolean** | `Field::make('active')->toggle()` on list columns |
| **Search toolbar** | Free text, filter chips, column picker, group-by, clear all |
| **New** | Links to `{formView}/create` (editable create form, not detail) |

## Form and detail views

| Feature | Detail |
|---------|--------|
| **Grid layout** | `->cols(n)`, per-section `cols:`, `Field::make('x')->colspan(2)` or `colspan('full')` |
| **Modes** | Display (detail), Edit, **New** (create) |
| **Breadcrumbs** | Session history in the shell; click a crumb to jump back without losing the trail |
| **Back / Cancel** | Uses navigation history (falls back to the list URL) |
| **After save/create** | Redirects to the **detail** view when one exists (not the list) |
| **Keyboard** | `Ctrl+S` / `Cmd+S` submits `#velm-form` |
| **Embedded forms** | `?embed=1` for record dialogs; `postMessage` updates parent M2M chips |
| **Relational widgets** | M2O combobox, M2M/O2M dialog widgets — work on **New** forms (same widgets as edit) |
| **`file_url` widget** | Char fields with `Field::make('logo_url')->widget('file_url')` — library picker, image preview, public attachment URLs |
| **`file` / `files` widgets** | M2O/M2M `ir.attachment` — single or multi library picker with chips (see [Attachment pickers](./views-and-forms#attachment-pickers-file-files)) |
| **M2O prefill** | Query params on create URL (e.g. `?project_id=3` from O2M “new line”) |

Stored view URLs:

| Page | Pattern |
|------|---------|
| List | `/velm/views/{module}/{listView}` |
| Detail | `/velm/views/{module}/{detailView}/{id}` |
| Edit | `/velm/views/{module}/{formView}/{id}/edit` |
| Create | `/velm/views/{module}/{formView}/create` |

Create and edit routes are registered **before** the generic `{record}` route so `/create` is not mistaken for a record id.

## Relational UI (dialogs)

| Type | Default UI |
|------|------------|
| **Many2one** | Search combobox, quick-create, open in dialog |
| **Many2many** | Inline chips; `Field::make('x')->widget('dialog')` for create/link dialog |
| **One2many** | Dialog table — create, link, open row, remove line |

Floating record dialog: `window.pvOpenRecord()`, iframe with `?embed=1`, parent notified on save (`velm-dialog-saved`).

ORM semantics: [Relational fields](../models/relational-fields). Authoring: [Views and forms](./views-and-forms).

## Demo addon (`demo_relations`)

Shipped with the monorepo demo app under `apps/demo/addons/demo_relations` (depends on **`file_manager`**):

| Model | Relations / widgets |
|-------|---------------------|
| `demo.project` | `tag_ids` (M2M), `task_ids` (O2M), `document_ids` (M2M **`files`**) |
| `demo.task` | `project_id` (M2O), `cover_id` (M2O **`file`**, `accept('image/*')`) |
| `demo.tag` | `name` |

Menus: **Demos → Projects**, **Demos → Tasks**. Install/sync:

```bash
php artisan velm:module:sync demo_relations
```

## HTTP JSON API

| Endpoint | Purpose |
|----------|---------|
| `GET/POST /api/records` | Search and create |
| `PATCH/DELETE /api/records/{id}` | Write and unlink |
| `GET /api/m2o/search` | Combobox search |
| `POST /api/m2o/quick-create` | Name-only quick create |
| `POST /api/attachment/upload` | Upload binary (optional `public`, `folder_id`) |
| `GET /api/attachment/{id}/download` | Download attachment bytes |
| `DELETE /api/attachment/{id}` | Remove attachment |

Datetime fields in API responses follow the same company timezone as the panel when `BindVelmEnvironment` runs on `/api/*`.

## See also

- [Admin panel](./admin-panel) — sign-in, catalog UI, branding, URLs
- [Views and forms](./views-and-forms) — list toolbar, widgets, menus
- [Scaffolding](./scaffolding) — `velm:make:view`, `velm:make:menu`
- [Security](../models/security) — access rights and record rules
- [Installation](./installation) — setup and module commands
