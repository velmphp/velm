<?php

declare(strict_types=1);

namespace Velm\Modules;

final class ManifestReader
{
    public function read(string $moduleDirectory): ModuleSpec
    {
        $manifestFile = rtrim($moduleDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'__velm__.php';

        if (! is_file($manifestFile)) {
            throw new \InvalidArgumentException("No __velm__.php manifest at {$moduleDirectory}.");
        }

        $manifest = require $manifestFile;

        if ($manifest instanceof Manifest) {
            $manifest = $manifest->toArray();
        }

        if (! is_array($manifest)) {
            throw new \InvalidArgumentException("__velm__.php at {$moduleDirectory} must return a Manifest or array.");
        }

        return ModuleSpec::fromManifest($manifest, $moduleDirectory);
    }
}
