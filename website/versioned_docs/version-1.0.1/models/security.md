---
sidebar_position: 6
---

# Security: access and record rules

Velm uses two layers, matching Odoo and PyVelm:

1. **`ir.model.access`** — table-level CRUD grants (can this user read *any* partner?).
2. **`ir.rule`** — row-level domains AND-merged into `search()` (which partners?).

Superuser (`uid = 1`) and `Environment::withAclBypass()` skip both layers (install hooks, migrations).

## Model access

Grant rows tie a `res.groups` (or everyone when `group_id` is empty) to a model name and four booleans: `perm_read`, `perm_write`, `perm_create`, `perm_unlink`.

```php
$env->checkAccess('res.partner', 'read'); // throws AccessDeniedException if denied
$env->hasAccess('res.partner', 'write');  // bool, for UI gating
$env->hasAccess('res.partner', 'unlink'); // bool — controls Delete on list rows
```

`Recordset` CRUD calls `checkAccess` before mutating data. If `ir.model.access` is not installed, access is open (tests and minimal registries).

### Shell UI gating

The admin panel uses the same flags for list row actions:

| Permission | List UI |
|------------|---------|
| `perm_read` | **Open** (when a detail view exists), click-to-open |
| `perm_write` | **Edit** |
| `perm_unlink` | **Delete** (trash icon; confirmation) — added automatically when allowed |

Configure access in **Settings** (admin module: **Users**, **Groups**, **Model access**, **Record rules**). Bootstrap user: `VELM_ADMIN_EMAIL` / `VELM_ADMIN_PASSWORD`. See [Admin panel — Settings](../guides/admin-panel#settings-admin-module) and [Views and forms](../guides/views-and-forms#row-actions-and-acl).

## Record rules

Rules store a JSON **domain** (list of leaves) on `ir.rule`:

```json
[
  ["name", "=", "Public"]
]
```

Placeholders in the third position are resolved at query time:

```json
[
  ["company_id", "=", {"placeholder": "company_id"}]
]
```

Supported placeholders: `uid`, `user_id`, `company_id` (from `Environment` context).

On `search()`, Velm appends every matching rule leaf to the caller domain. Multiple rules are **AND**ed together. Group rules apply only when the user belongs to that group; global rules (`group_id` empty) apply to everyone who already passed model access.

### OR-groups in domains

Search domains and record-rule JSON support **prefix notation** for boolean groups (PyVelm/Odoo style):

| Prefix | Meaning |
|--------|---------|
| `\|` | OR — next two subtrees |
| `&` | AND — next two subtrees (default between leaves) |
| `!` | NOT — next subtree |

Example — active partners in Belgium **or** France:

```json
["|", ["country_id.code", "=", "BE"], ["country_id.code", "=", "FR"], ["active", "=", true]]
```

Legacy `__or__` keys in JSON domains are still accepted. The domain compiler applies the same syntax in `search()`, record rules, and list view filters.

```php
// User domain + rule leaves → SQL WHERE
$partners = $env->model('res.partner')->search([['active', '=', true]]);
```

### What rules do not filter (v1)

- `read()` on explicit ids (`browse([$id])->read()`)
- `write()` / `unlink()` by id without a prior search

List views and `GET /api/records` use `search()`, so rules apply there.

## Seeding (install hook)

Create access before rules. Use ACL bypass while seeding:

```php
$env->withAclBypass(function () use ($env, $salesGroupId): void {
    $env->model('ir.model.access')->create([
        'name' => 'Sales/res.partner read',
        'model' => 'res.partner',
        'group_id' => $salesGroupId,
        'perm_read' => true,
    ]);

    $env->model('ir.rule')->create([
        'name' => 'Sales/own partners',
        'model' => 'res.partner',
        'group_id' => $salesGroupId,
        'perm_read' => true,
        'domain' => json_encode([
            ['create_uid', '=', ['placeholder' => 'uid']],
        ]),
    ]);
});
```

Idempotent seeds: search for an existing rule by `name` + `model` before `create`.

## See also

- [RFC 0002](https://github.com/velmphp/velm/blob/main/docs/rfcs/0002-record-rules.md) — full design
- [Models overview](./index.md)
