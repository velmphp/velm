<?php

declare(strict_types=1);

namespace Velm\Modules\Autoload;

/**
 * Runtime PSR-4-style autoloading for Velm module classes without per-addon composer.json entries.
 */
final class ModuleClassAutoloader
{
    /** @var list<ModuleClassResolver> */
    private static array $resolvers = [];

    private static bool $registered = false;

    /**
     * @param  array<string, list<string>|callable(): list<string>>  $prefixRoots
     */
    public static function register(array $prefixRoots): void
    {
        foreach ($prefixRoots as $prefix => $roots) {
            $resolvedRoots = is_callable($roots) ? $roots() : $roots;

            self::$resolvers[] = new ModuleClassResolver($prefix, $resolvedRoots);
        }

        if (! self::$registered) {
            spl_autoload_register([self::class, 'load']);
            self::$registered = true;
        }
    }

    public static function reset(): void
    {
        if (self::$registered) {
            spl_autoload_unregister([self::class, 'load']);
        }

        self::$resolvers = [];
        self::$registered = false;
    }

    public static function load(string $class): void
    {
        foreach (self::$resolvers as $resolver) {
            $file = $resolver->resolve($class);

            if ($file !== null) {
                require_once $file;

                return;
            }
        }
    }
}
