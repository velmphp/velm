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

### Attachment pickers (`file`, `files`)

For relational attachment fields (stores attachment id(s), not a URL string):

```php
Field::make('attachment_id')->widget('file'),
Field::make('attachment_ids')->widget('files'),
Field::make('cover_id')->widget('file')->accept('image/*'),
```

| Widget | Field type | Behavior |
|--------|------------|----------|
| **`file`** | `Many2oneField('ir.attachment')` | Single chip + **Pick a file** → library picker dialog |
| **`files`** | `Many2manyField('ir.attachment')` | Chip list + multi-select picker |

List columns resolve attachment **display names** when these widgets are used. Requires **`file_manager`** and `ir.attachment`.

### Rich text (`rich_text`)

For long HTML content on `text` fields (descriptions, notes):

```php
Field::make('description')->richText()->wide(),
```

| Behavior | Detail |
|----------|--------|
| Editor | TipTap 3 (StarterKit: bold, italic, headings, lists, links, undo/redo; image resize/align via file library) |
| Storage | HTML string in the field |
| Display | Rendered HTML on detail/read-only forms |
| Layout | Use `->wide()` or `->colspan('full')` for a comfortable editing area |

### Mail thread & chatter

Enable discussion by declaring the **`mail.thread`** mixin (requires the **`mail`** module):

```php
class Change extends Model
{
    protected static ?string $name = 'it.change';
    protected static ?string $table = 'it_change';
    protected static array $mixins = ['mail.thread'];

    // ...
}
```

The shorthand **`$mailThread = true`** on the model class (or on an extension via `$inherit`) still works for backward compatibility.

On **display** record pages, a **Chatter** sidebar loads next to the form (under the workflow panel when both apply). Users can post log notes, read the message history, and follow/unfollow the record.

| Piece | Detail |
|-------|--------|
| Storage | `mail.message` and `mail.follower` rows keyed by `(model, res_id)` |
| API | `GET /web/mail/thread`, `POST /web/mail/messages`, `POST /web/mail/follow` |
| Module | Install **`mail`**; chatter appears only on models with `$mailThread = true` |

### Code editor (`code`)

For JSON, scripts, or other structured text on `text` fields:

```php
Field::make('definition')->code('json')->wide(),
```

| Option | Detail |
|--------|--------|
| `->code('json')` | Syntax highlighting (`json`, `javascript`, `html`, `css`, `python`, …) |
| Editor | CodeMirror 6 with line numbers; follows light/dark shell theme |
| Display | Monospace `pre` block on detail pages |

After changing `packages/ui` JavaScript, run `cd packages/ui && npm install && npm run build`, then `php artisan vendor:publish --tag=velm-ui-assets --force` in the app.

### Record dialog

Related records open in a **draggable floating dialog** (iframe with `?embed=1`):

- Header: title, **Open full page**, close
- Body: full form with **Save** / **Create** / **Cancel**
- After save/create, the iframe navigates to the record (embed) and notifies the parent so **many2many** chips can update

Implemented in `window.pvOpenRecord()` / `Alpine.store('recordDialog')`. The file picker uses the same dialog shell via `window.PvDialog`.

## View inheritance

Extend an existing view arch without copying the whole form or list. Register inherits in a module `views/*.php` file via `ViewsData::make()->inherits(...)`.

### Authoring API

Use `InheritView` with a fluent builder. Patch the parent view identified as `module.view_name` (e.g. `partners.partner.form`):

```php
use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\InheritView;
use Velm\Views\Authoring\Section;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->inherits(
        InheritView::make('partner.form.ext')
            ->extends('partners.partner.form')
            ->setCols(2)
            ->updateSection('identity', title: 'Contact', cols: 2)
            ->afterField('identity', 'name', Field::make('website'))
            ->removeSection('organization', 'address')
            ->afterSection(
                'identity',
                Section::make('location', 'Location')->cols(2)->fields('company_id', 'country_id'),
            ),
    );
```

| Method | Purpose |
|--------|---------|
| `->extends('module.view')` | Parent view ref (required) |
| `->setCols(n)` | Set root column count on form/detail |
| `->updateSection(name, title:, cols:, …)` | Merge attrs into a section |
| `->removeSection('a', 'b', …)` | Remove sections by name |
| `->afterField` / `->beforeField` | Insert a field relative to another field |
| `->afterSection` / `->beforeSection` | Insert a `Section` arch node |
| `->appendInSection` / `->prependInSection` | Add fields at end or start of a section |
| `->updateColumn` / `->removeColumn` | Patch list view columns |

`Field`, `Section`, and plain field name strings (`'website'`) are normalized automatically.

Low-level ops (`update`, `after`, `remove`, dot paths, `ViewTarget`) remain available for advanced patches.

After changing inherit files:

```bash
php artisan velm:module:sync <module>
```

### Third-party modules and apply order

Several modules may inherit the same parent view (e.g. two addons both patch `partners.partner.form`). Velm does **not** rely on authors setting `priority` to coordinate.

| Rule | Behavior |
|------|----------|
| **Module `depends`** | Inherits apply in **installed module dependency order** (same topo sort as model MRO). If B `depends('partners_ext')`, B's patches always run after `partners_ext`. |
| **Missing targets** | If an earlier patch removed a section or field, later ops targeting it are **skipped** (page still loads). Default: `velm.views.skip_missing_inherit_targets` = `true`. Set `VELM_VIEWS_SKIP_MISSING_INHERIT_TARGETS=false` in dev to fail loud. |
| **Unrelated siblings** | Order is stable but undefined between peers with no `depends` link; design patches to tolerate no-ops or declare `depends` on the layout module you build on. |

Protected modules (`base`, `admin`, and `velm.bootstrap_modules`) cannot be uninstalled.

**Demo:** addon [`partners_ext`](https://github.com/velmphp/velm/blob/main/apps/demo/addons/partners_ext/views/partner.php) — model `$inherit` plus form/detail view inherits.

See also [Stacking extensions](../models/stacking-extensions#models-vs-views) (models vs views).

## Dashboard views

Per-model **dashboard** boards are stored views (`view_type: dashboard`) — distinct from the panel **home dashboard** at `/velm/dashboard` (module `dashboard.php` hooks).

```php
use Velm\Views\Authoring\DashboardView;
use Velm\Views\Authoring\Widgets\ChartWidget;
use Velm\Views\Authoring\Widgets\StatWidget;
use Velm\Views\Authoring\Widgets\TableWidget;

DashboardView::make('partner.dashboard')
    ->model('res.partner')
    ->title('Partners overview')
    ->columns(2)
    ->listView('partner.list')
    ->widgets([
        StatWidget::make('total')
            ->title('Total contacts')
            ->icon('heroicon-o-user-group'),
        StatWidget::make('companies')
            ->title('Companies')
            ->domain([['is_company', '=', true]]),
        TableWidget::make('recent')
            ->title('Recent contacts')
            ->view('partner.list')
            ->limit(5)
            ->size('full'),
        ChartWidget::make('by_country')
            ->title('By country')
            ->view('partner.graph')
            ->size('full'),
    ]);
```

| API | Purpose |
|-----|---------|
| `->columns(n)` | Grid width for widget layout (1–12) |
| `->listView('…')` | Default list URL for stat/table “view all” links |
| `->domain([...])` | Optional base domain for all widgets |
| `StatWidget::make('id')` | Count card; optional `->domain()`, `->measure()`, `->size('half'|'full')` |
| `TableWidget::make('id')` | Recent rows from a **list** view arch (`->view('partner.list')`, `->limit(5)`) |
| `ChartWidget::make('id')` | Bar chart from a **graph** view arch (`->view('partner.graph')`) |

Register a menu item pointing at the dashboard view, then sync:

```bash
php artisan velm:module:sync partners
```

When a module has a dashboard view, the **app rail** and catalog **Open app** link open that board by default (even if the list menu item has a lower sequence). Set an explicit `->href('…')` on a menu **group** to override landing URL.

URL: `/velm/views/{module}/{dashboardView}` (e.g. `/velm/views/partners/partner.dashboard`).

## Menus

Register menus in `views/menu.php` and sync the module. Menu entries point at list view URLs (`/velm/views/{module}/{view}.list`).

```bash
php artisan velm:make:menu --view=product.list --module=inventory
php artisan velm:module:sync inventory
```

See [Scaffolding](./scaffolding#velmmakemenu).

## Demo module

The monorepo demo app installs **`demo_relations`** (Projects, Tasks, Tags) under **Demos** — useful for trying M2O, O2M, and M2M in the shell. See [Relational fields](../models/relational-fields).

## See also

- [Platform features](./features) — timestamps, catalog, ACL UI, demo module
- [Relational fields](../models/relational-fields) — ORM semantics for M2O / O2M / M2M
- [Admin panel](./admin-panel) — navigation and branding
- [Scaffolding](./scaffolding) — `velm:make:view` and menus
