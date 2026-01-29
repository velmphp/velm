<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Velm\Core\Compiler\VelmModelCompiler;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class VelmCompileCommand extends Command
{
    protected $signature = 'velm:compile {--lazy : Compile in lazy mode}';

    protected $description = 'Compile Velm Domain Classes to Proxies';

    /**
     * @throws \ReflectionException
     */
    public function handle(): int
    {
        // Here would be the logic to compile Velm models and proxies.
        intro('Compiling Velm models and proxies...');
        $lazy = $this->option('lazy') ?? false;
        /* ============== MODELS ========================= */
        app(VelmModelCompiler::class)->compile($lazy);
        /* ============== POLICIES ========================= */
        // Additional compilation logic for proxies would go here.
        outro('Compilation completed successfully.');

        return SymfonyCommand::SUCCESS;
    }
}
