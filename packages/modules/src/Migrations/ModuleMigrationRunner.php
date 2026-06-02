<?php

declare(strict_types=1);

namespace Velm\Modules\Migrations;

use Velm\Environment;
use Velm\Modules\ModuleSpec;
use Velm\Modules\ModuleVersion;

final class ModuleMigrationRunner
{
    /**
     * @param  list<int>  $fromVersion  Installed version (exclusive end of applied range).
     * @param  list<int>  $toVersion    Manifest target (inclusive).
     */
    public function run(Environment $env, ModuleSpec $spec, array $fromVersion, array $toVersion): void
    {
        $directory = $spec->path.'/migrations';

        if (! is_dir($directory)) {
            return;
        }

        $width = max(count($fromVersion), count($toVersion), 3);
        $fromPadded = ModuleVersion::pad($fromVersion, $width);
        $toPadded = ModuleVersion::pad($toVersion, $width);

        $files = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if (! str_ends_with($entry, '.php')) {
                continue;
            }

            $stem = substr($entry, 0, -4);
            $parsed = ModuleVersion::parseMigrationFilename($stem);

            if ($parsed === null) {
                throw new \InvalidArgumentException(
                    "Migration {$entry} in {$spec->name} must match <from>_to_<to>.php (e.g. 0_1_0_to_0_2_0.php).",
                );
            }

            [$fileFrom, $fileTo] = $parsed;
            $fileFromPadded = ModuleVersion::pad($fileFrom, $width);
            $fileToPadded = ModuleVersion::pad($fileTo, $width);

            if (ModuleVersion::compare($fileToPadded, $fromPadded) > 0
                && ModuleVersion::compare($fileToPadded, $toPadded) <= 0) {
                $files[$entry] = $directory.'/'.$entry;
            }
        }

        ksort($files);

        foreach ($files as $path) {
            $this->runFile($env, $path);
        }
    }

    private function runFile(Environment $env, string $path): void
    {
        $upgrade = require $path;

        if (! is_callable($upgrade)) {
            throw new \RuntimeException("Migration {$path} must return a callable (Environment \$env) => void.");
        }

        $upgrade($env);
    }
}
