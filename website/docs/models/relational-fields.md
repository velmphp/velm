---
sidebar_position: 5
---

# Relational fields

Velm supports the three standard Odoo-style relations on models. All are declared in `defineFields()` and flow through `Recordset::create`, `read`, and `write`.

## Many2one

Stored as an integer FK column on the model's table.

```php
use Velm\Fields\Many2oneField;

'company_id' => Many2oneField::make('res.company')->label('Company'),
```

Values in create/write are integer ids (or `null`).

## One2many

Not stored on the parent table. The inverse **Many2one** on the comodel holds the link.

```php
use Velm\Fields\One2manyField;

'line_ids' => One2manyField::make('sale.order.line', 'order_id')->label('Lines'),
```

On the comodel:

```php
'order_id' => Many2oneField::make('sale.order'),
```

- **Read** returns a list of child record ids for each parent.
- **Write** on a **single** parent record accepts an id list and replaces links (children not in the list are unlinked by clearing the inverse FK).
- Registration validates that the inverse field exists and points back at the parent model.

Optional UI hints: `->listView('order.line.list')`, `->formView('order.line.form')`. In forms, One2many uses the **dialog** widget by default (table + create/link/open in a floating record dialog). See [Views and forms](../guides/views-and-forms#relational-fields-in-the-ui).

## Many2many

Stored in a junction table (auto-created on schema sync). Not a column on either model table.

```php
use Velm\Fields\Many2manyField;

'group_ids' => Many2manyField::make('res.groups')->label('Groups'),
```

Custom junction table/columns:

```php
'tag_ids' => Many2manyField::make('test.tag')
    ->relation('article_tag_rel', 'article_id', 'tag_id'),
```

- **Read** returns a list of related ids per record.
- **Write** replaces the junction rows for each parent (same id-list semantics as PyVelm).

Self-referential many2many requires an explicit `->relation(...)`.

## Schema sync

`SchemaBuilder::syncRegistry()` creates:

- Tables and columns for stored fields (Char, Integer, Many2one, …)
- Junction tables for Many2many
- **No** column for One2many (inverse Many2one on the comodel is synced instead)

## Try it in the shell

The skeleton **`demo_relations`** addon (`apps/skeleton/addons/demo_relations`) installs **Demos → Projects** with M2M tags, O2M tasks, and M2O `project_id` on tasks. UI widgets (dialog, combobox, embed forms) are described in [Views and forms](../guides/views-and-forms#relational-fields-in-the-ui) and [Platform features](../guides/features#relational-ui-dialogs).

## Example

```php
// Parent
'line_ids' => One2manyField::make('test.order.line', 'order_id'),

// Child
'order_id' => Many2oneField::make('test.order'),
```

```php
$order = $env->model('test.order')->create(['name' => 'SO001']);
$line = $env->model('test.order.line')->create([
    'order_id' => $order->ids()[0],
    'description' => 'Widget',
]);

$order->read(['line_ids']); // [['line_ids' => [1]]]
$order->write(['line_ids' => [$line->ids()[0]]]); // single parent only
```

See [Scaffolding](../guides/scaffolding) for generating modules that use these fields, and [Module migrations](../guides/migrations) for schema upgrades.

## UI behavior (summary)

| Field | In forms |
|-------|----------|
| **Many2one** | Search combobox, quick create, open/edit in record dialog |
| **Many2many** | Inline chips, or `widget: dialog` for Create new / Link existing |
| **One2many** | Dialog table (default) or `widget: inline` when supported |

Record dialogs load the comodel form with `?embed=1` and show the full **Save** / **Create** action bar. See [Views and forms](../guides/views-and-forms).

**Try it:** after [Installation](../guides/installation), open **Demos → Projects** (module `demo_relations`).
