<?php

namespace Velm\Core\Commands\Generator;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class PolicyMakeCommand extends \Illuminate\Foundation\Console\PolicyMakeCommand
{
    use InteractsWithVelmModules;

    protected $name = 'velm:make:policy';

    protected $description = 'Create a new policy class inside a velm module';

    protected function getDefaultNamespace($rootNamespace)
    {
        $module = $this->resolveModule();
        if (! is_dir($module->entryPoint::getAppPath('Policies'))) {
            mkdir($module->entryPoint::getAppPath('Policies'), 0755, true);
        }

        return $rootNamespace.'\\Policies';
    }

    protected function findAvailableModels()
    {
        $module = $this->resolveModule();
        $modelPath = $module->entryPoint::getModelsPath();

        $res = (new Collection(Finder::create()->files()->depth(0)->in($modelPath)))
            ->map(fn ($file) => $file->getBasename('.php'))
            ->sort()
            ->values()
            ->all();

        return $res;
    }
}
