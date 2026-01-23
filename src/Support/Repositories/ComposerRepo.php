<?php

namespace Velm\Core\Support\Repositories;

class ComposerRepo
{
    /**
     * @throws \JsonException
     */
    public function getComposerJson(string $packageName): ?array
    {
        return velm()->registry()->modules()->getComposerJson($packageName);
    }

    /**
     * @throws \JsonException
     */
    public function getVelmModule(string $packageName): ?array
    {
        $package = $this->getComposerJson($packageName);
        if (empty($package)) {
            return null;
        }
        // Check if the type is velm-module
        if (! (isset($package['type']) && $package['type'] === 'velm-module')) {
            return null;
        }
        // Check if extra.module is set
        if (! isset($package['extra']['velm']['module'])) {
            return null;
        }
        // Check if extra.module is an instance of VelmModule
        $entryClass = $package['extra']['velm']['module'];
        if (! $entryClass) {
            return null;
        }

        // Skip checking validity of class due to its performance expense
        return $package;
    }
}
