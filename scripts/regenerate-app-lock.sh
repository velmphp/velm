#!/usr/bin/env bash
# Regenerate apps/app/composer.lock from Packagist only (no monorepo path repos).
# Run AFTER v1.0.0-rc2 (or later) is indexed on Packagist for all velmphp/* packages.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

cp "$ROOT/apps/app/composer.json" "$TMP/composer.json"
cd "$TMP"

echo "Updating dependencies from Packagist only..."
composer update --no-interaction --prefer-dist

cp composer.lock "$ROOT/apps/app/composer.lock"
echo "Wrote $ROOT/apps/app/composer.lock"

grep -q '"type": "path"' "$ROOT/apps/app/composer.lock" && {
  echo "ERROR: lock still contains path repositories — aborting." >&2
  exit 1
}

echo "OK — no path repos in lock."
