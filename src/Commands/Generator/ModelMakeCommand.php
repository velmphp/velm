<?php

namespace Velm\Core\Commands\Generator;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use IntractsWithVelmModules;
    protected $name = 'velm:make:model';

    protected function getDefaultNamespace($rootNamespace): string
    {
        $module = $this->resolveModule();
        return (is_dir(velm_app_path($module->packageName,"Models")) ? $rootNamespace."\\Models" : $rootNamespace);
    }
    protected function replaceClass($stub, $name): array|string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $dot_class = str($class)->snake()->replace('_', '.')->toString();
        $content = parent::replaceClass($stub, $name);
        return str_replace(['{{ dot_class }}', '{{dot_class}}'], $dot_class, $content);
    }
}
