<?php

namespace Velm\Core\Commands\Generator;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use IntractsWithVelmModules;

    protected $name = 'velm:make:model';

    protected function getDefaultNamespace($rootNamespace): string
    {
        $module = $this->resolveModule();
        // If Models directory does not exist, create it first
        if (! is_dir(velm_app_path($module->packageName, 'Models'))) {
            mkdir(velm_app_path($module->packageName, 'Models'), 0755, true);
        }

        return $rootNamespace.'\\Models';
    }

    protected function replaceClass($stub, $name): array|string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $dot_class = str($class)->snake()->replace('_', '.')->toString();
        $content = parent::replaceClass($stub, $name);

        return str_replace(['{{ dot_class }}', '{{dot_class}}'], $dot_class, $content);
    }
}
