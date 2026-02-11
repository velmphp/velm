<?php

namespace Velm\Core\Commands\Generator;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Core\Modules\ModuleDescriptor;

use function Laravel\Prompts\select;

trait InteractsWithVelmModules
{
    public function resolveModule(): ModuleDescriptor
    {
        $moduleName = $this->argument('module');
        if (! $moduleName) {
            $moduleName = $this->askForModuleName();
        }

        return velm()->registry()->modules()->findOrFail($moduleName, bySlug: true);
    }

    protected function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            new InputArgument(
                name: 'module',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the module where the model will be created (e.g., Accounting)',
            ),
        ]);
    }

    protected function askForModuleName(): int|bool|array|string|null
    {
        $moduleName = $this->argument('module');
        if (! $moduleName) {
            // Suggestions from the installed modules
            $installed = \velm()->registry()->modules()->resolved();
            $suggestions = collect($installed)->mapWithKeys(function (ModuleDescriptor $item) {
                return [$item->slug => $item->packageName];
            })->all();

            $moduleName = select('Module name (e.g., Accounting)', $suggestions);
            $this->input->setArgument('module', $moduleName);
        }
        // Set the module argument
        $this->input->hasArgument('module') && $this->input->setArgument('module', $moduleName);

        return $moduleName;
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $this->askForModuleName();
        parent::afterPromptingForMissingArguments($input, $output);
    }

    protected function rootNamespace(): string
    {
        return $this->resolveModule()->namespace.'\\';
    }

    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        $module = $this->resolveModule();

        return velm_app_path($module->packageName, str_replace('\\', '/', $name).'.php');
    }

    protected function resolveStubPath($stub): string
    {
        return __DIR__.$stub;
    }

    protected function qualifyModel(string $model)
    {
        $model = ltrim($model, '\\/');

        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        // Proxy namespace to the module's namespace
        return 'Velm\\Models\\'.$model;
    }
}
