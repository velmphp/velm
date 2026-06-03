---
sidebar_position: 5
---

# Views and forms

Velm views are PHP files under each module’s `views/` directory. They declare **list**, **form**, and **detail** arch JSON via `ViewsData`, `ListView`, `FormView`, and `DetailView` (see [Scaffolding](./scaffolding)).

After changing view files, sync the module:

```bash
php artisan velm:module:sync <module>
```

Sync writes views and menus from disk into `ir.ui.view` / `ir.ui.menu` and **deletes** module views/menus that were removed from data files (matching menu prune behavior).

## List views

### Authoring

```php
use Velm\Views\Authoring\ListView;
use Velm\Views\Authoring\ListRowAction;
use Velm\Views\Authoring\Field;

ListView::make('partner.list')
    ->model('res.partner')
    ->title('Partners')
    ->formView('partner.form')
    ->detailView('partner.detail')
    ->clickToOpen()
    ->rowActions([
        ListRowAction::open(),
        ListRowAction::edit(),
        ListRowAction::delete(), // optional; also auto-added when perm_unlink
    ])
    ->columns([
        'name',
        Field::make('active')->toggle(),
        'company_id',
    ]);
```

| API | Purpose |
|-----|---------|
| `->detailView('…')` + `->clickToOpen()` | Click a row (or **Open**) to open the detail page |
| `->formView('…')` | **New** button → `/velm/views/{module}/{formView}/create` (editable form, not detail) |
| `->rowActions([...])` | Per-row icon actions in the last column |

`velm:make:view` scaffolds list + form + detail with **Open**, **Edit**, and **Delete** row actions.

### Search toolbar

Every list includes a PyVelm-style **search bar**:

- Free-text search (debounced)
- **Filter chips** (removable; includes search and field filters)
- **Columns** — toggle visible columns
- **Filters** — dropdown with **Filter by** (boolean, many2one) and **Group by**
- **clear all** when any filter or grouping is active

Boolean filters offer Yes/No; many2one filters include an inline search against `/api/m2o/search`.

### Row actions and ACL

| Action | Shown when |
|--------|------------|
| **Open** | `detailView` is set and user has `perm_read` |
| **Edit** | Form/edit route exists and user has `perm_write` |
| **Delete** | User has `perm_unlink` (trash icon; confirmation dialog) |

**Delete** is appended automatically when `perm_unlink` is granted, even if omitted from `rowActions`. Delete calls `Recordset::unlink()` and refreshes the list.

See [Security](../models/security) for `ir.model.access`.

### List columns

Use `Field::make('active')->toggle()` for boolean columns that can be toggled inline on the list.

You may add `created_at` / `updated_at` to list columns explicitly; values are shown in the **active company timezone** (stored as UTC). Scaffolds omit them by default.

## Form and detail layout

### Column grid

Control responsive column counts on forms and detail pages:

```php
FormView::make('partner.form')
    ->model('res.partner')
    ->cols(3)                              // default columns for sections
    ->section('main', 'Main', ['name'], cols: 2)
    ->section('notes', 'Notes', [
        Field::make('comment')->colspan(2), // span two columns
        Field::make('active')->toggle(),
    ]);
```

| API | Purpose |
|-----|---------|
| `->cols(n)` | Default section grid width (1–12) |
| `->section(..., cols: n)` | Override columns for one section |
| `Field::make('x')->colspan(2)` | Field spans multiple columns |
| `Field::make('x')->colspan('full')` | Full width (`wide`) |

Same options apply to `DetailView::make(...)`.

### Form actions bar

Forms and detail pages show a sticky **actions bar**:

| Mode | Actions |
|------|---------|
| **display** | **Edit**, **Back** |
| **edit** | **Save**, **Cancel** |
| **new** | **Create**, **Cancel** |

**Keyboard:** `Ctrl+S` / `Cmd+S` submits the form (`#velm-form`) from any field.

Embedded forms (`?embed=1`, used inside record dialogs) show the same bar; **Cancel/Close** closes the parent dialog instead of navigating away.

## Relational fields in the UI

### Many2one

- Combobox search via `/api/m2o/search`
- Optional **quick create** (name typed + Enter)
- **Create and edit** opens the comodel form in a floating dialog when a form view exists
- **Open** icon on the selected value (read-only / display)
- On **New** parent records, many2one fields use the same `m2o-input` widget as edit (not a plain text field)
- Create URLs accept query prefill: `/create?project_id=3` sets the inverse many2one when opening from an O2M line

### Many2many

Two presentations:

| Widget | Use |
|--------|-----|
| Inline chips + search | Default on edit forms |
| `->widget('dialog')` on the field in arch | **Create new** + **Link existing…** buttons |

**Dialog widget** (`dialogOnly`):

- **Create new** — opens comodel create form in the record dialog
- **Link existing…** — search and add related ids
- Click a chip — opens comodel **edit** in the dialog

### One2many

Default **dialog** widget: table of linked rows with **Create new**, **Link existing**, open row in dialog, and remove line.

Use `->widget('inline')` in field arch when you want the relation edited inline (future/table embed).

### File URL (`file_url`)

For char fields that store a public URL (logos, favicons, static assets):

```php
Field::make('logo_url')->widget('file_url'),
Field::make('logo_url_dark')->widget('file_url')->whenEmptyUse('logo_url'),
```

| Behavior | Detail |
|----------|--------|
| **Browse…** | Opens the file library picker in the record dialog (`/web/files/picker`) |
| **Stored value** | `/api/attachment/{id}/download` (relative URL) |
| **Public flag** | Picked attachments are marked public for use in the shell header |
| **Preview** | Image preview when the URL points at an image or attachment download |
| **Dark logo** | `whenEmptyUse('logo_url')` shows the light logo in the field preview when dark is unset |

Requires the **`file_manager`** module and `ir.attachment` API. Used on **Settings → Companies → Branding**.

### Record dialog

Related records open in a **draggable floating dialog** (iframe with `?embed=1`):

- Header: title, **Open full page**, close
- Body: full form with **Save** / **Create** / **Cancel**
- After save/create, the iframe navigates to the record (embed) and notifies the parent so **many2many** chips can update

Implemented in `window.pvOpenRecord()` / `Alpine.store('recordDialog')`. The file picker uses the same dialog shell via `window.PvDialog`.

## Menus

Register menus in `views/menu.php` and sync the module. Menu entries point at list view URLs (`/velm/views/{module}/{view}.list`).

```bash
php artisan velm:make:menu --view=product.list --module=inventory
php artisan velm:module:sync inventory
```

See [Scaffolding](./scaffolding#velmmakemenu).

## Demo module

The skeleton installs **`demo_relations`** (Projects, Tasks, Tags) under **Demos** — useful for trying M2O, O2M, and M2M in the shell. See [Relational fields](../models/relational-fields).

## See also

- [Platform features](./features) — timestamps, catalog, ACL UI, demo module
- [Relational fields](../models/relational-fields) — ORM semantics for M2O / O2M / M2M
- [Admin panel](./admin-panel) — navigation and branding
- [Scaffolding](./scaffolding) — `velm:make:view` and menus
