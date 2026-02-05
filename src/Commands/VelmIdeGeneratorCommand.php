<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Velm\Core\Ide\ModelStubsGenerator;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class VelmIdeGeneratorCommand extends Command
{
    protected $name = 'velm:ide-generate';

    protected $aliases = ['velm:ide'];

    protected $description = 'Generate IDE helper files for Velm projects.';

    public function __invoke(): void
    {
        // Generate model stubs
        intro('Generating IDE helper files for Velm projects...');
        $modelsGenerator = new ModelStubsGenerator;
        $modelsGenerator->generate();
        outro("IDE helper files generated successfully. You may need to refresh your IDE's cache for the changes to take effect.");
    }
}
