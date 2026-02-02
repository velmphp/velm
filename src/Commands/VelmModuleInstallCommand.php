<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Console\Input\InputArgument;

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
        // Next, install it in the persistence layer if needed
        $registry = \Velm::registry()->modules();
        $registry->install($package, \Velm::tenant());
        outro("Module $package installed successfully.");
    }
}
