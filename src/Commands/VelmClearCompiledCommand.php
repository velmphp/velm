<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Velm\Core\Compiler\GeneratedPaths;

class VelmClearCompiledCommand extends Command
{
    protected $signature = 'velm:clear-compiled';

    protected $description = 'Clear the compiled Velm model proxies cache';

    public function handle(): int
    {
        $cachePath = GeneratedPaths::base();
        if (is_dir($cachePath)) {
            $this->info("Clearing compiled Velm model proxies cache at: $cachePath");
            // Recursively delete the cache directory
            app('files')->deleteDirectory($cachePath, true);
            $this->info('Cache cleared successfully.');
        } else {
            $this->info("No compiled Velm model proxies cache found at: $cachePath");
        }

        return SymfonyCommand::SUCCESS;
    }
}
