<?php

namespace Velm\Core\Commands;

use Illuminate\Console\Concerns\PromptsForMissingInput;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Velm\Core\Support\Constants;

use function Laravel\Prompts\text;

class VelmMakeCommand extends \Illuminate\Console\Command implements \Illuminate\Contracts\Console\PromptsForMissingInput
{
    use PromptsForMissingInput;

    // Generate a new Velm module
    protected $name = 'velm:make:module';

    protected $aliases = ['velm:make-module', 'velm:make'];

    protected $description = 'Generate a new Velm module';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'What is the name of the module?',
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        // Ask for the package name after the module name is provided
        if ($this->hasArgument('package') && $input->getArgument('package')) {
            return;
        }
        $name = $input->getArgument('name');
        $defaultPackage = 'modules/'.str($name)->kebab()->toString();
        $package = text('Package name (e.g., vendor/package)', placeholder: $defaultPackage, default: $defaultPackage);
        $input->setArgument('package', $package);
    }

    protected function askForPackageName(): string
    {
        if ($this->hasArgument('package') && $this->argument('package')) {
            return $this->argument('package');
        }
        $name = $this->argument('name');
        $defaultPackage = 'modules/'.str($name)->kebab()->toString();
        $package = text('Package name (e.g., vendor/package)', placeholder: $defaultPackage, default: $defaultPackage);
        $this->input->setArgument('package', $package);

        return $package;
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::REQUIRED,
                description: 'The name of the module',
            ),
            new InputArgument(
                name: 'package',
                mode: InputArgument::OPTIONAL,
                description: 'The package name (e.g., vendor/package)',
            ),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'force',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Force the creation of the module even if it already exists',
            ),
        ];
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $replacements = $this->getReplacements();
        $kebabCaseName = str($name)->kebab()->toString();
        $studlyName = str($name)->studly()->toString();
        // Ensure the modules directory exists
        if (! is_dir(base_path(Constants::MODULES_DIRECTORY))) {
            mkdir(base_path(Constants::MODULES_DIRECTORY), 0755, true);
        }
        $modulePath = base_path(Constants::MODULES_DIRECTORY."/{$kebabCaseName}");

        if (is_dir($modulePath) && ! $this->option('force')) {
            $this->error("Module '{$name}' already exists.");

            return SymfonyCommand::FAILURE;
        }

        try {
            // Generate module files and directories in memory, then copy them to the module path
            $directories = [
                'app',
                'config',
                'database/migrations',
                'database/seeders',
                'database/factories',
                'resources/views',
                'resources/lang/en',
                'public',
                'routes',
                'tests',
            ];
            $force = $this->option('force');
            foreach ($directories as $dir) {
                // If directory does not exist, create it
                $fullPath = "{$modulePath}/{$dir}";
                // if directory exists, delete it since force is true
                if (is_dir($fullPath) && $force) {
                    // recursively delete directory and do not throw Directory not empty error
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $fileinfo) {
                        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                        $todo($fileinfo->getRealPath());
                    }
                    rmdir($fullPath);
                }
                if (! is_dir($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
            }
            $stubs = [
                'module/Module.stub' => "{$modulePath}/app/{$studlyName}Module.php",
                'config.stub' => "{$modulePath}/config/config.php",
                'module/web-routes.stub' => "{$modulePath}/routes/web.php",
                'module/api-routes.stub' => "{$modulePath}/routes/api.php",
                'module/console-routes.stub' => "{$modulePath}/routes/console.php",
                'module/composer.stub' => "{$modulePath}/composer.json",
            ];
            foreach ($stubs as $stub => $destination) {
                $stubContent = file_get_contents(__DIR__.'/stubs/'.$stub);
                $replacedContent = str_replace(array_keys($replacements), array_values($replacements), $stubContent);
                file_put_contents($destination, $replacedContent);
            }
            $this->info("Module '{$name}' created successfully at {$modulePath}.");

            return SymfonyCommand::SUCCESS;
        } catch (\Exception $e) {
            // Cleanup on failure
            $this->error("Failed to create module '{$name}': ".$e->getMessage());
            if (is_dir($modulePath)) {
                rmdir($modulePath);
            }

            return SymfonyCommand::FAILURE;
        }
    }

    private function getReplacements(): array
    {
        $name = $this->argument('name');
        $snakeCaseName = str($name)->snake()->toString();
        $kebabCaseName = str($name)->kebab()->toString();
        $studlyName = str($name)->studly()->toString();
        $package = $this->askForPackageName();
        $slug = $kebabCaseName;
        // For namespace, explode package by '/' and studly case each part
        $namespace = collect(explode('/', $package))
            ->map(fn ($part) => str($part)->studly()->toString())
            ->implode('\\');
        $escaped_namespace = str($namespace)->replace('\\', '\\\\')->toString();

        // For the title, separate words by spaces and capitalize each word
        $title = str($studlyName)->replaceMatches('/([a-z])([A-Z])/', '$1 $2')->toString();

        $replacements = [
            'package' => $package,
            'name' => $name,
            'studly_name' => $studlyName,
            'snake_name' => $snakeCaseName,
            'kebab_name' => $kebabCaseName,
            'namespace' => $namespace,
            'escaped_namespace' => $escaped_namespace,
            'slug' => $slug,
            'studly_title' => $title,
        ];
        // For each of them, consider both {{ key }} and {{key}} formats
        $finalReplacements = [];
        foreach ($replacements as $key => $value) {
            $finalReplacements["{{ {$key} }}"] = $value;
            $finalReplacements["{{{$key}}}"] = $value;
        }

        return $finalReplacements;
    }
}
