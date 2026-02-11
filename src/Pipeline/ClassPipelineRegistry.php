<?php

namespace Velm\Core\Pipeline;

use Velm\Core\Pipeline\Contracts\Pipelinable;

final class ClassPipelineRegistry
{
    /** @var array<string, object[]> */
    private static array $registry = [];

    /** @var array<string, string[]> */
    private static array $staticRegistry = [];

    // Register instance pipeline
    public static function register(object $instance, ?string $logicalName = null): void
    {
        /**
         * @var Pipelinable $instance
         */
        $logicalName ??= $instance->getLogicalName();
        self::$registry[$logicalName][] = $instance;
    }

    // Register static pipeline
    public static function registerStatic(string $className, ?string $logicalName = null): void
    {
        $logicalName ??= (new $className)->getLogicalName();
        self::$staticRegistry[$logicalName][] = $className;
    }

    /** @return object[] */
    public static function extensionsFor(string $logicalName): array
    {
        $logicalName = velm_utils()->formatVelmName($logicalName);

        return self::$registry[$logicalName] ?? [];
    }

    /** @return string[] */
    public static function staticExtensionsFor(string $logicalName): array
    {
        $logicalName = velm_utils()->formatVelmName($logicalName);

        return self::$staticRegistry[$logicalName] ?? [];
    }

    public function all(): array
    {
        return self::$registry;
    }

    public function allStatic(): array
    {
        return self::$staticRegistry;
    }

    public function find(string $logicalName): array
    {
        return self::extensionsFor($logicalName);
    }

    public function findStatic(string $logicalName): array
    {
        return self::staticExtensionsFor($logicalName);
    }

    public function firstExtensionFor(string $logicalName): ?object
    {
        $extensions = self::extensionsFor($logicalName);

        return $extensions[0] ?? null;
    }

    final public static function runtime(): ClassPipelineRuntime
    {
        return new ClassPipelineRuntime;
    }
}
