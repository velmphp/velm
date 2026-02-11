<?php

namespace Velm\Core\Commands\Generator;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use InteractsWithVelmModules;

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
}
