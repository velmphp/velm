---
sidebar_position: 1
---

# Documentation Velm

**Velm** est un framework ERP basé sur Laravel avec une sémantique de type PyVelm : modules façon Odoo, recordsets et héritage de vues, rendus via le panneau d’administration Velm (Livewire + Tailwind).

**Installation depuis Packagist :** `composer create-project velmphp/app my_app` — voir [Installation](./guides/installation).

Ce site documente comment **écrire des modules** et utiliser l’ORM. Pour l’architecture du dépôt et le contexte contributeur, voir le monorepo [PLAN.md](https://github.com/velmphp/velm/blob/main/PLAN.md) et [CONTEXT.md](https://github.com/velmphp/velm/blob/main/CONTEXT.md).

:::info Traduction en cours
Les guides détaillés sont encore en anglais. Consultez la version **1.0.0** en anglais pour le contenu stable le plus récent.
:::

## Plan de la documentation

| Sujet | Par où commencer |
|-------|------------------|
| **Installation** | [Installation](./guides/installation) |
| Modules applicatifs et autoloading | [Modules applicatifs](./guides/addons) |
| Migrations de modules | [Migrations de modules](./guides/migrations) |
| Génération de modules, modèles, vues | [Scaffolding](./guides/scaffolding) |
| Modèles — champs, registre, recordsets | [Modèles](./models/) |

## PyVelm

L’implémentation de référence Python : [github.com/coolsam726/pyvelm](https://github.com/coolsam726/pyvelm).
