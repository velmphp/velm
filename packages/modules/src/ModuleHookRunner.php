<?php

declare(strict_types=1);

namespace Velm\Modules;

use Velm\Environment;

final class ModuleHookRunner
{
    public function runInstallHook(?string $hook, Environment $env): void
    {
        if ($hook === null || $hook === '') {
            return;
        }

        $this->resolve($hook, 'INSTALL_HOOK')($env);
    }

    public function runSyncHook(?string $hook, Environment $env): void
    {
        if ($hook === null || $hook === '') {
            return;
        }

        $this->resolve($hook, 'SYNC_HOOK')($env);
    }

    /**
     * @return callable(Environment): void
     */
    private function resolve(string $hook, string $label): callable
    {
        if (! str_contains($hook, '::')) {
            throw new \InvalidArgumentException(
                "{$label} must be a Class::method reference, got {$hook}.",
            );
        }

        [$class, $method] = explode('::', $hook, 2);

        if ($class === '' || $method === '') {
            throw new \InvalidArgumentException(
                "{$label} must be a Class::method reference, got {$hook}.",
            );
        }

        if (! class_exists($class)) {
            throw new \RuntimeException("{$label} class {$class} was not found.");
        }

        if (! method_exists($class, $method)) {
            throw new \RuntimeException("{$label} method {$class}::{$method} was not found.");
        }

        $callable = [$class, $method];

        if (! is_callable($callable)) {
            throw new \RuntimeException("{$label} {$hook} is not callable.");
        }

        return static function (Environment $env) use ($callable): void {
            $callable($env);
        };
    }
}
