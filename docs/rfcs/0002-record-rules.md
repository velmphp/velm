# RFC 0002: Record rules (`ir.rule`) and domain injection

| Field | Value |
|-------|-------|
| **Status** | Implemented |
| **Depends on** | [RFC 0001](./0001-model-record-methods.md) (MRO / recordset); `ir.model.access` (shipped on `feature/acl-record-rules`) |
| **Authors** | Velm contributors |
| **Created** | 2026-06-02 |

## Summary

**Model access** (`ir.model.access`) answers: “May this user perform `read` / `write` / `create` / `unlink` on this model at all?” **Record rules** (`ir.rule`) answer: “Which rows?” by storing per-model, per-permission domain fragments that are **AND-merged** into `search()` (and any API that lists rows through search).

This RFC specifies the `ir.rule` schema, `Environment::collectRecordRules()`, placeholder substitution, injection into the ORM domain pipeline, install-time seeding conventions, and tests—mirroring PyVelm’s `env.collect_record_rules()` and `BaseModel._collect_search_domain()`.

## Motivation

### Problem

1. **Coarse grants are not enough** — A user may have `read` on `res.partner` but must only see partners they own or partners in their company. Table-level ACL cannot express row sets.
2. **Parity with PyVelm / Odoo** — Apps and migrations seed `ir.rule` rows; the runtime must apply them consistently on list/search paths.
3. **HTTP and Filament list views** — `GET /api/records` and arch list pages call `Recordset::search()`; without rule injection, UI leaks rows that rules were meant to hide.

### Non-goals (this RFC)

- **`_company_scoped` automatic filter** — PyVelm also ANDs `('company_id', '=', env.company_id)` for opted-in models; Velm defers that to a follow-up (needs `company_id` on `Environment` / session).
- **Per-record enforcement on `read()` / `write()` / `unlink()` by id** — PyVelm applies rules on **search** (and `read_group`); browsing or patching a known id without a prior search is not re-checked in this slice. Document as limitation; row-level checks can be a later RFC.
- **Domain OR-groups inside a single rule** — Rule `domain` is a flat list of leaves ANDed together (same as today’s Velm domain compiler). Nested `['|', …]` in JSON is out of scope until the domain compiler supports it.
- **Rule caching across requests** — Per-environment, per-model/perm memoization only (like access cache).
- **Policies (`env.can` / `check_can`)** — PyVelm’s record-aware policy layer stays future work.

## Background: two-layer security

```text
Request → Environment(uid, groups)
              │
              ├─ ir.model.access  →  checkAccess(model, perm)   [gate: all or nothing]
              │
              └─ ir.rule          →  collectRecordRules(model, perm)
                                    → AND into search domain     [filter: which rows]
```

| Layer | Model | Question |
|-------|--------|----------|
| Access | `ir.model.access` | Can this principal do `perm` on `model`? |
| Rule | `ir.rule` | Which records match for `perm` on `model`? |

**Superuser** (`uid = 1`) and **`withAclBypass()`** skip both layers (installer, migrations, loading `ir.rule` itself).

**Anonymous** (`uid = null`): only **global** rules (`group_id` empty) apply; group-specific rules are ignored (PyVelm convention).

## Schema: `ir.rule`

Bundled in the **base** module next to `ir.model.access` (PyVelm: `modules/base/models/security.py`).

| Field | Type | Notes |
|-------|------|--------|
| `name` | Char, required | Label |
| `model` | Char, required | Target model `_name` (e.g. `res.partner`) |
| `group_id` | Many2one → `res.groups` | `null` = global rule (all principals that pass access) |
| `perm_read` | Boolean, default true | Rule applies when collecting for `read` / `search` |
| `perm_write` | Boolean, default true | For `write` collection (future injection points) |
| `perm_create` | Boolean, default true | Reserved; create has no row set yet |
| `perm_unlink` | Boolean, default true | Reserved |
| `domain` | Text, required | JSON array of domain leaves |

Example row (seeded by an app install hook):

```json
[
  ["user_id", "=", {"placeholder": "uid"}]
]
```

After resolution at query time:

```php
['user_id', '=', 2]
```

### Placeholders

Resolved in `Environment::resolveRulePlaceholder(string $name): mixed`:

| Placeholder | Value |
|-------------|--------|
| `uid`, `user_id` | `Environment::$uid` |
| `company_id` | `Environment::$context['company_id']` or dedicated property (TBD in implementation) |

Unknown placeholders throw `InvalidArgumentException` (fail loud during development).

Placeholder dicts may appear as the **third element** of a leaf, or inside **list** values (e.g. `in` operator) — same rules as PyVelm `_resolve_rule_leaves`.

## Design

### 1. `Environment::collectRecordRules(string $modelName, string $perm): array`

Returns a **flat list of domain leaves** (each `list{string, string, mixed}`) to AND into the caller’s domain.

Algorithm (matches PyVelm `collect_record_rules`):

1. If superuser or ACL bypass → `[]`.
2. If `ir.rule` not in registry → `[]` (backward compatible with tests / minimal registries).
3. Under `withAclBypass`, search `ir.rule`:
   - `('model', '=', $modelName)`
   - `('perm_'.$perm, '=', true)`
   - If anonymous: `('group_id', '=', null)` only.
   - Else: global rules **union** rules for `group_id in userGroupIds()`.
4. For each matching rule, `json_decode(domain)` → resolve placeholders → append leaves to output.
5. Return concatenation of all rule leaf lists (multiple rules = multiple AND constraints).

**Recursion note:** Reading `rule.domain` triggers `read` on `ir.rule`. Collection must run under bypass so ACL on `ir.rule` does not deny the lookup (chicken-and-egg).

### 2. Search domain merge (`Recordset::search`)

Introduce private `collectSearchDomain(array $userDomain, string $perm): array`:

```php
$domain = $userDomain;
foreach ($this->env->collectRecordRules($this->modelName(), $perm) as $leaf) {
    $domain[] = $leaf;
}
return $domain;
```

`search()` flow:

1. `checkAccess($model, 'read')` — already implemented.
2. `$domain = collectSearchDomain($domain, 'read')`.
3. Existing `buildWhere` / SQL (supports `=`, `in`, null, `ilike`, …).

**Semantics:** Multiple rules and user filters are **AND**ed. There is no OR between separate `ir.rule` rows (same as PyVelm).

### 3. Other call sites (this RFC)

| Call site | Behavior |
|-----------|----------|
| `GET /api/records` | Uses `search()` → rules applied automatically |
| `RecordQuery::assertExists` | Uses `search([['id', '=', $id]])` → hidden ids return 404 |
| `Many2oneSearch` | Uses `search()` → combobox respects rules |
| `read()` with explicit ids | **No filter** in v1 — documented limitation |
| `write()` / `unlink()` | **Access check only** in v1 — no row rule re-validation |

### 4. Install / seeding

- **Do not** seed a global company rule on `res.partner` in base (PyVelm removed it; company scoping is model-level when `_company_scoped` exists).
- Apps seed rules in **`INSTALL_HOOK`** when they need row-level security (e.g. “user sees only own leads”).
- Idempotent seeds: search for existing rule by `(name, model)` before `create`.

### 5. Interaction with `ir.model.access`

Both must pass:

1. User lacks `read` on `res.partner` → `AccessDeniedException` before domain merge.
2. User has `read` but rule says `('user_id', '=', uid)` → search only returns matching rows.
3. Superuser → no access or rule restrictions.

Record methods (`$recordset->badge()`) do **not** call `collectRecordRules`; authors must not rely on rules inside code that uses `browse([$id])` without a search unless they enforce scope themselves.

## Comparison with PyVelm

| | PyVelm | Velm (this RFC) |
|--|--------|------------------|
| Rule storage | `ir.rule.domain` JSON text | Same |
| Collection | `env.collect_record_rules(model, perm)` | `Environment::collectRecordRules` |
| Injection | `_collect_search_domain` on search / read_group | `Recordset::collectSearchDomain` on `search` |
| Placeholders | `uid`, `user_id`, `company_id` | Same set; `company_id` from context |
| Write-time row check | Not in core model CRUD | Same (deferred) |
| `sudo()` / bypass | `_acl_bypass` | `withAclBypass()` |

## API surface (to implement)

| Symbol | Package | Role |
|--------|---------|------|
| `Rule` model | `velm/modules` base | `ir.rule` table |
| `Environment::collectRecordRules` | `velm/core` | Gather AND leaves |
| `Environment::resolveRulePlaceholder` | `velm/core` | Placeholder → scalar |
| `Recordset::collectSearchDomain` | `velm/core` | Merge user domain + rules |
| Manifest / seed helpers | docs + examples | Idempotent rule creation |

## Errors

| Situation | Exception |
|-----------|-----------|
| Invalid JSON in `ir.rule.domain` | `InvalidArgumentException` |
| Unknown placeholder | `InvalidArgumentException` |
| Unknown domain field on rule leaf | `InvalidArgumentException` (from `buildWhere`) |
| Access denied (no model grant) | `AccessDeniedException` (existing) |

## Testing

Add alongside existing ACL tests (`packages/core/tests/AclTest.php`, `packages/modules/tests/Feature/AclInstallTest.php`):

1. **Rule hides rows** — User with global `read` on model but rule `('name', '=', 'Secret')` only sees public rows.
2. **Placeholder `uid`** — Rule `('user_id', '=', {placeholder: uid})`; two users see disjoint sets.
3. **Group rule** — Rule tied to `res.groups`; user without group does not get that AND leaf (only global rules).
4. **Superuser / bypass** — Sees all rows despite rules.
5. **API** — `GET /api/records` count matches filtered search.
6. **No `ir.rule` in registry** — Search behaves as today (no regression).

Target: full suite stays green; +6–8 focused tests.

## Documentation

After implementation:

- `website/docs/models/security.md` — access vs rules, placeholder examples, seeding snippet.
- Cross-link from `website/docs/models/index.md`.
- Update [ROADMAP.md](../../ROADMAP.md): record rules → Done.

## Migration / compatibility

- **Additive** — registries without `ir.rule` unchanged.
- **Apps that relied on “open search” with only table ACL** may need explicit global rules or grants; base already seeds broad admin access, not row rules.
- Installing `base` adds the `ir_rule` table; no automatic rules beyond docs/examples.

## Open questions

1. **`read()` filtering** — Should `read()` drop ids that fail rule domains (Odoo-style), or leave as-is for performance?
2. **`write` / `unlink` validation** — Re-search each id against `collectRecordRules(..., 'write'|'unlink')` before mutating?
3. **`company_id` on Environment** — Promote from `context` to a first-class property for placeholders and `_company_scoped`?
4. **Rule OR semantics** — Support `['|', leaf, leaf]` inside stored JSON when domain compiler gains OR groups?
5. **Caching** — Per-request cache keyed by `(model, perm)` for collected leaves?

## References

- PyVelm: `pyvelm/env.py` (`collect_record_rules`, `_resolve_rule_leaves`), `pyvelm/modules/base/models/security.py` (`Rule`)
- Velm ACL (implemented): `packages/core/src/Environment.php`, `packages/modules/modules/base/models/ModelAccess.php`
- Domain compiler: `packages/core/src/Recordset/Recordset.php` (`buildWhere`, `in`, null)
- Prior: [RFC 0001](./0001-model-record-methods.md) — record methods; ACL in `__call` remains optional/future

## Decision

**Accept** — Shipped with `ir.rule` model, `collectRecordRules()`, `search()` injection, tests, and `website/docs/models/security.md`. Row-level checks on `read()` / `write()` by id remain deferred (open questions 1–2).
