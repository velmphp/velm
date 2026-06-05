<?php

declare(strict_types=1);

namespace Velm\Modules\Autoload;

use Velm\Modules\Support\ModuleNaming;

/**
 * Resolves module PHP classes to files under Velm addon roots.
 *
 * Convention (app addons and bundled modules share the layout):
 * - {@code Addons\Inventory\Models\Product} → {@code addons/inventory/models/Product.php}
 * - {@code Velm\Modules\Partners\Models\Partner} → {@code modules/partners/models/Partner.php}
 * - {@code Addons\ChangeManagement\Dashboard\Widget} → {@code addons/change_management/Dashboard/Widget.php}
 */
final class ModuleClassResolver
{
    /** @var array<string, string> snake_case module name → absolute module path */
    private array $modulePathCache = [];

    /**
     * @param  list<string>  $roots
     */
    public function __construct(
        private readonly string $prefix,
        private readonly array $roots,
    ) {}

    public function resolve(string $class): ?string
    {
        if (! str_starts_with($class, $this->prefix)) {
            return null;
        }

        $relative = substr($class, strlen($this->prefix));

        if ($relative === '' || ! str_contains($relative, '\\')) {
            return null;
        }

        /** @var list<string> $parts */
        $parts = explode('\\', $relative);
        $studlyModule = array_shift($parts);

        if ($studlyModule === null || $studlyModule === '' || $parts === []) {
            return null;
        }

        $modulePath = $this->findModulePath(ModuleNaming::studlyToSnake($studlyModule));

        if ($modulePath === null) {
            return null;
        }

        $className = array_pop($parts);

        if ($className === null || $className === '') {
            return null;
        }

        $segments = [];

        foreach ($parts as $segment) {
            $segments[] = $segment === 'Models' ? 'models' : $segment;
        }

        $file = $modulePath;

        if ($segments !== []) {
            $file .= DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);
        }

        $file .= DIRECTORY_SEPARATOR.$className.'.php';

        return is_file($file) ? $file : null;
    }

    private function findModulePath(string $snakeModule): ?string
    {
        if (isset($this->modulePathCache[$snakeModule])) {
            return $this->modulePathCache[$snakeModule];
        }

        foreach ($this->roots as $root) {
            if ($root === '' || ! is_dir($root)) {
                continue;
            }

            $candidate = rtrim($root, '/\\').DIRECTORY_SEPARATOR.$snakeModule;

            if (is_file($candidate.'/__velm__.php')) {
                return $this->modulePathCache[$snakeModule] = $candidate;
            }
        }

        return null;
    }
}
