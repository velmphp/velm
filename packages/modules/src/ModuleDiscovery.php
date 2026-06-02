<?php

declare(strict_types=1);

namespace Velm\Modules;

final class ModuleDiscovery
{
    public function __construct(
        private readonly ManifestReader $manifestReader = new ManifestReader,
    ) {}

    /**
     * @param  list<string>  $roots
     * @return array<string, ModuleSpec>
     */
    public function discover(array $roots): array
    {
        $specs = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $entries = scandir($root) ?: [];

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $modulePath = $root.DIRECTORY_SEPARATOR.$entry;

                if (! is_dir($modulePath)) {
                    continue;
                }

                $manifestFile = $modulePath.DIRECTORY_SEPARATOR.'__velm__.php';

                if (! is_file($manifestFile)) {
                    continue;
                }

                $spec = $this->manifestReader->read($modulePath);

                if (isset($specs[$spec->name])) {
                    throw new \RuntimeException(sprintf(
                        'Duplicate module name %s (%s and %s).',
                        $spec->name,
                        $specs[$spec->name]->path,
                        $modulePath,
                    ));
                }

                $specs[$spec->name] = $spec;
            }
        }

        ksort($specs);

        return $specs;
    }
}
