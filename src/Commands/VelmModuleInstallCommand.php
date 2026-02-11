<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Console\Input\InputArgument;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\outro;

class VelmModuleInstallCommand extends Command implements PromptsForMissingInput
{
    use \Illuminate\Console\Concerns\PromptsForMissingInput;

    protected $name = 'velm:module:install';

    protected $description = 'Install a Velm module';

    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'package',
                mode: InputArgument::REQUIRED,
                description: 'The package name of the module to install (e.g., vendor/package)',
            ),
        ];
    }

    /**
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function __invoke()
    {
        $package = $this->argument('package');
        $registry = \velm()->registry()->modules();
        $existing = $registry->find($package);
        if (! $existing) {
            $this->warn("Module $package not found in the registry. Running composer require...");
            // Run composer require
            velm()->composer()->require($package);
            // Refresh the registry
            alert('To complete the installation, please run the module install command again.');

            return;
        }
        $registry->install($package, velm()->tenant());
        // First, run composer require if the module is not already installed
        // Next, install it in the persistence layer if needed
        outro("Module $package installed successfully.");
    }
}
