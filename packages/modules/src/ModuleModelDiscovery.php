<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Fields\One2manyField;
use Velm\Models\Model;
use Velm\Modules\Support\ModuleNaming;

/**
 * Discover model classes from the conventional {@code models/} directory.
 *
 * Manifest {@code MODELS} remains supported for classes outside {@code models/}
 * (e.g. test fixtures, shared support namespaces).
 */
final class ModuleModelDiscovery
{
    /**
     * @return list<class-string<Model>>
     */
    public static function discover(string $modulePath, string $moduleName): array
    {
        $modelsDir = rtrim($modulePath, '/\\').DIRECTORY_SEPARATOR.'models';

        if (! is_dir($modelsDir)) {
            return [];
        }

        $namespace = ModuleNaming::modelsNamespace($modulePath, $moduleName);
        /** @var list<class-string<Model>> $classes */
        $classes = [];

        foreach (scandir($modelsDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || ! str_ends_with($entry, '.php')) {
                continue;
            }

            $stem = substr($entry, 0, -4);

            if ($stem === '') {
                continue;
            }

            $fqn = $namespace.'\\'.ModuleNaming::classNameFromStem($stem);
            $file = $modelsDir.DIRECTORY_SEPARATOR.$entry;

            require_once $file;

            if (! class_exists($fqn, false)) {
                continue;
            }

            if (! is_subclass_of($fqn, Model::class)) {
                continue;
            }

            $classes[] = $fqn;
        }

        return self::sortByRegistrationOrder($classes);
    }

    /**
     * @param  list<class-string<Model>>  $classes
     * @return list<class-string<Model>>
     */
    public static function sortByRegistrationOrder(array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        /** @var list<class-string<Model>> $bases */
        $bases = [];
        /** @var list<class-string<Model>> $extensions */
        $extensions = [];

        foreach ($classes as $class) {
            if ($class::isExtension()) {
                $extensions[] = $class;
            } else {
                $bases[] = $class;
            }
        }

        /** @var array<string, class-string<Model>> $nameToClass */
        $nameToClass = [];

        foreach ($bases as $class) {
            $class::initialize();
            $name = $class::name();

            if ($name !== null && $name !== '') {
                $nameToClass[$name] = $class;
            }
        }

        /** @var array<class-string<Model>, list<string>> $dependsOn */
        $dependsOn = [];

        foreach ($bases as $class) {
            $dependsOn[$class] = [];

            foreach ($class::baseFields() as $field) {
                if ($field instanceof One2manyField && $field->comodel !== '') {
                    if (isset($nameToClass[$field->comodel])) {
                        $dependsOn[$class][] = $field->comodel;
                    }
                }
            }
        }

        /** @var array<class-string<Model>, int> $inDegree */
        $inDegree = array_fill_keys($bases, 0);

        foreach ($bases as $class) {
            $inDegree[$class] = count($dependsOn[$class]);
        }

        /** @var list<class-string<Model>> $queue */
        $queue = [];

        foreach ($bases as $class) {
            if ($inDegree[$class] === 0) {
                $queue[] = $class;
            }
        }

        sort($queue);

        /** @var list<class-string<Model>> $sorted */
        $sorted = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $sorted[] = $current;

            $currentName = $current::name();

            if ($currentName === null || $currentName === '') {
                continue;
            }

            foreach ($bases as $other) {
                if (! in_array($currentName, $dependsOn[$other], true)) {
                    continue;
                }

                $inDegree[$other]--;

                if ($inDegree[$other] === 0) {
                    $queue[] = $other;
                    sort($queue);
                }
            }
        }

        if (count($sorted) !== count($bases)) {
            sort($bases);

            $sorted = $bases;
        }

        sort($extensions);

        return [...$sorted, ...$extensions];
    }

    /**
     * @param  list<class-string>  $explicit
     * @return list<class-string<Model>>
     */
    public static function resolve(string $modulePath, string $moduleName, array $explicit): array
    {
        $discovered = self::discover($modulePath, $moduleName);
        $explicit = array_values(array_unique($explicit));
        $extra = array_values(array_filter(
            $explicit,
            static fn (string $class): bool => ! in_array($class, $discovered, true),
        ));

        /** @var list<class-string<Model>> $merged */
        $merged = [...$discovered, ...self::sortByRegistrationOrder($extra)];

        return array_values(array_unique($merged));
    }
}
