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
    protected bool $allModules = false;

    public function resolveModule(): ?ModuleDescriptor
    {
        $moduleName = $this->argument('module');
        if (! $moduleName) {
            $moduleName = $this->askForModuleName();
        }
        if ($moduleName === '_') {
            if (! $this->canRunOnAllModules()) {
                $this->error('This command cannot be run on all modules. Please specify a module.');
                exit(1);
            }
            $this->allModules = true;

            return null;
        }

        return velm()->registry()->modules()->findOrFail($moduleName);
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

    protected function canRunOnAllModules(): bool
    {
        return false;
    }

    protected function askForModuleName(): int|bool|array|string|null
    {
        $moduleName = $this->argument('module');
        if (! $moduleName) {
            // Suggestions from the installed modules
            $installed = \velm()->registry()->modules()->resolved();
            $suggestions = collect($installed)->mapWithKeys(function (ModuleDescriptor $item) {
                return [$item->packageName => $item->packageName];
            })->all();
            if ($this->canRunOnAllModules()) {
                $suggestions = array_merge(['_' => 'All Modules'], $suggestions);
            }

            $moduleName = select('Module name (e.g., Accounting)', $suggestions);
            $this->input->setArgument('module', $moduleName);
        }
        // Set the module argument
        $this->input->hasArgument('module') && $this->input->setArgument('module', $moduleName);

        return $moduleName;
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        parent::afterPromptingForMissingArguments($input, $output);
        $this->askForModuleName();
    }

    protected function rootNamespace(): string
    {
        if ($this->allModules) {
            return parent::rootNamespace();
        }

        return $this->resolveModule()->namespace.'\\';
    }

    protected function getPath($name): string
    {
        if ($this->allModules) {
            return parent::getPath($name);
        }
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
