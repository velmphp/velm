# Velm

<p align="center">
  <a href="https://github.com/velmphp/velm/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/velmphp/velm/ci.yml?branch=main&style=for-the-badge&logo=github&label=CI" alt="CI"></a>
  <a href="https://codecov.io/gh/velmphp/velm"><img src="https://img.shields.io/codecov/c/github/velmphp/velm?branch=main&style=for-the-badge&logo=codecov&label=coverage" alt="Test coverage"></a>
  <a href="https://packagist.org/packages/velmphp/framework"><img src="https://img.shields.io/packagist/v/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=Packagist" alt="Packagist version"></a>
  <a href="https://packagist.org/packages/velmphp/framework/stats"><img src="https://img.shields.io/packagist/dt/velmphp/framework?style=for-the-badge&logo=packagist&logoColor=white&label=downloads" alt="Packagist downloads"></a>
</p>

<p align="center">
  <a href="https://www.php.net/releases/8.3.php"><img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+"></a>
  <a href="./LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License MIT"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-4-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 4"></a>
  <a href="https://pestphp.com"><img src="https://img.shields.io/badge/Pest-4-6ADE80?style=for-the-badge&logo=pest&logoColor=white" alt="Pest 4"></a>
</p>

<p align="center">
  <strong>Odoo-like modules on Laravel.</strong>
</p>

Velm is a modular ERP framework for PHP: installable modules per database, a recordset ORM, declarative view arch, and a Livewire admin shell. Built on **Laravel 13**, **Livewire 4**, and **PHP 8.3+**. Packages publish as [`velmphp/*`](https://packagist.org/packages/velmphp/) on Packagist.

Semantic port of [PyVelm](https://github.com/coolsam726/pyvelm).

## Quick start

```bash
composer create-project velmphp/app my_app
cd my_app && composer run setup
```

Open `/velm` and sign in with `admin@velm.test` / `password`.

## Documentation

**[velmphp.github.io/velm](https://velmphp.github.io/velm/)** — installation, models, views, and module authoring.

Monorepo preview: `cd website && npm install && npm start`

## This repository

Development monorepo for `velmphp/*` packages (`packages/`) and bundled modules (`packages/modules/modules/`). Runnable demo app: [`apps/demo/`](./apps/demo/README.md).

```bash
composer install
composer test
```

## License

MIT — see [LICENSE](./LICENSE).
