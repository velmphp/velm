# Skeleton addons

App-specific Velm modules live here. The monorepo ships one demo:

## `demo_relations`

Demonstrates **Many2one**, **One2many**, and **Many2many** (see [Relational fields](https://github.com/velmphp/velm/blob/main/website/docs/models/relational-fields.md)):

| Model | Fields |
|-------|--------|
| `demo.project` | `tag_ids` (M2M → `demo.tag`), `task_ids` (O2M → `demo.task`) |
| `demo.task` | `project_id` (M2O → `demo.project`) |
| `demo.tag` | `name` |

Installed by `composer run setup`. Open **Demos → Projects** in the Velm shell.

Sample API checks after setup:

```bash
# List projects with relations (superuser / ACL bypass in dev)
curl -s 'http://127.0.0.1:8000/api/records?model=demo.project&fields=name,tag_ids,task_ids' | jq

# Tasks for a project
curl -s 'http://127.0.0.1:8000/api/records?model=demo.task&fields=name,project_id' | jq
```

Reinstall seed data: `php artisan velm:module:sync demo_relations` (idempotent hook skips if projects exist).
