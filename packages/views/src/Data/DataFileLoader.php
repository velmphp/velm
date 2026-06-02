<?php

declare(strict_types=1);

namespace Velm\Views\Data;

use Velm\Modules\ModuleSpec;
use Velm\Views\Authoring\Contracts\ViewDeclaration;

final class DataFileLoader
{
    /**
     * @return array{views: list<array<string, mixed>>, view_inherits: list<array<string, mixed>>}
     */
    public function load(ModuleSpec $spec): array
    {
        $views = [];
        $inherits = [];

        if ($spec->data === []) {
            return ['views' => $views, 'view_inherits' => $inherits];
        }

        foreach ($spec->data as $relativePath) {
            $path = $spec->path.DIRECTORY_SEPARATOR.$relativePath;

            if (! is_file($path)) {
                throw new \RuntimeException(
                    "Module {$spec->name}: data file {$relativePath} not found at {$path}",
                );
            }

            $suffix = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($suffix !== 'php') {
                throw new \RuntimeException(
                    "Module {$spec->name}: data file {$relativePath} must be a .php file.",
                );
            }

            $exported = require $path;

            if ($exported instanceof ViewsData) {
                $exported = $exported->toArray();
            }

            if (! is_array($exported)) {
                throw new \RuntimeException(
                    "Module {$spec->name}: data file {$relativePath} must return ViewsData or an array.",
                );
            }

            $views = array_merge($views, $this->expandViews($exported['VIEWS'] ?? []));
            $inherits = array_merge($inherits, $this->expandInherits($exported['VIEW_INHERITS'] ?? []));
        }

        return ['views' => $views, 'view_inherits' => $inherits];
    }

    /**
     * @param  list<mixed>  $declarations
     * @return list<array<string, mixed>>
     */
    private function expandViews(array $declarations): array
    {
        $views = [];

        foreach ($declarations as $declaration) {
            if ($declaration instanceof ViewDeclaration) {
                $views[] = $declaration->toArray();

                continue;
            }

            if (is_array($declaration)) {
                $views[] = $declaration;
            }
        }

        return $views;
    }

    /**
     * @param  list<mixed>  $declarations
     * @return list<array<string, mixed>>
     */
    private function expandInherits(array $declarations): array
    {
        $inherits = [];

        foreach ($declarations as $declaration) {
            if (is_array($declaration)) {
                $inherits[] = $declaration;
            }
        }

        return $inherits;
    }
}
