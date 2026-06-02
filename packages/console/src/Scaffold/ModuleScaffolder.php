<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

final class ModuleScaffolder
{
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,49}$/';

    /**
     * @param  list<string>  $depends
     */
    public function scaffold(string $name, string $addonRoot, array $depends = ['base']): string
    {
        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw new \InvalidArgumentException(
                'Module name must be snake_case: start with a letter, then letters, digits, or underscores (max 50 chars).',
            );
        }

        $modulePath = rtrim($addonRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;

        if (is_dir($modulePath)) {
            throw new \RuntimeException("Module directory already exists: {$modulePath}");
        }

        if (! is_dir($addonRoot) && ! mkdir($addonRoot, 0775, true) && ! is_dir($addonRoot)) {
            throw new \RuntimeException("Could not create addon root: {$addonRoot}");
        }

        mkdir($modulePath.'/models', 0775, true);
        mkdir($modulePath.'/migrations', 0775, true);

        file_put_contents($modulePath.'/__velm__.php', $this->manifestContents($name, $depends));
        file_put_contents($modulePath.'/models/.gitkeep', '');
        file_put_contents($modulePath.'/migrations/.gitkeep', '');

        return $modulePath;
    }

    /**
     * @param  list<string>  $depends
     */
    private function manifestContents(string $name, array $depends): string
    {
        $display = str_replace('_', ' ', ucwords($name, '_'));
        $dependsArgs = implode(', ', array_map(
            static fn (string $dep): string => "'".addslashes($dep)."'",
            $depends,
        ));

        return <<<PHP
<?php

declare(strict_types=1);

use Velm\Modules\Manifest;

return Manifest::make('{$name}')
    ->version(0, 1, 0)
    ->depends({$dependsArgs})
    ->summary('{$display} module.')
    ->category('Apps');

PHP;
    }
}
