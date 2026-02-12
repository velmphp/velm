<?php

namespace Velm\Core;

use Illuminate\Console\Command;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Velm\Core\Commands\Generator\MigrateMakeCommand;
use Velm\Core\Commands\Generator\ModelMakeCommand;
use Velm\Core\Commands\Generator\PolicyMakeCommand;
use Velm\Core\Commands\VelmClearCompiledCommand;
use Velm\Core\Commands\VelmIdeGeneratorCommand;
use Velm\Core\Commands\VelmMakeCommand;
use Velm\Core\Commands\VelmMigrateCommand;
use Velm\Core\Commands\VelmModuleInstallCommand;
use Velm\Core\Commands\VelmModuleMigrateCommand;
use Velm\Core\Persistence\Contracts\ModuleStateRepository;
use Velm\Core\Persistence\JsonModuleStateRepository;
use Velm\Core\Support\Constants;

class VelmServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('velm')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations(static::getMigrations())
            ->hasCommands(static::getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('velmphp/framework')
                    ->startWith(function (Command $command) {
                        $command->info('Velm Framework installation started');
                        // Create 'path' repositories for Modules path
                        $modulesPath = base_path(Constants::MODULES_DIRECTORY);
                        if (! file_exists($modulesPath)) {
                            mkdir($modulesPath, 0755, true);
                            $command->info("Created Modules directory at $modulesPath");
                        }
                        // Modify composer.json
                        $composerPath = base_path('composer.json');
                        $symlinkPath = Constants::MODULES_DIRECTORY.'/*';
                        $composerJson = json_decode(file_get_contents($composerPath), true);
                        if (isset($composerJson['repositories'])) {
                            $found = false;
                            foreach ($composerJson['repositories'] as $repository) {
                                if (isset($repository['type']) && $repository['type'] === 'path' &&
                                    isset($repository['url']) && $repository['url'] === $symlinkPath) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (! $found) {
                                $composerJson['repositories'][] = [
                                    'type' => 'path',
                                    'url' => $symlinkPath,
                                    'options' => [
                                        'symlink' => true,
                                    ],
                                ];
                                file_put_contents($composerPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                $command->info('Added path repository for modules path to composer.json');
                            } else {
                                $command->info('Path repository for modules already exists in composer.json');
                            }
                        } else {
                            $composerJson['repositories'] = [[
                                'type' => 'path',
                                'url' => $symlinkPath,
                                'options' => [
                                    'symlink' => true,
                                ],
                            ]];
                            file_put_contents($composerPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                            $command->info('Added path repository for modules path to composer.json');
                        }
                        $command->info('Please run "composer dump-autoload" to update the autoloader.');
                    })
                    ->endWith(function (Command $command) {
                        $command->alert('Velm Framework installation completed');
                    });
            });
        $this->app->singleton('velm', function ($app) {
            return new Velm;
        });

        // Bind the Module State Repository
        // Only bind if DB has velm_modules table
        if (app()->runningInConsole() && ! \Schema::hasTable('velm_modules')) {
            // Bind to JSON repository if table does not exist
            $this->app->bind(
                ModuleStateRepository::class,
                \Velm\Core\Persistence\JsonModuleStateRepository::class
            );

            return;
        }
        $this->app->bind(
            ModuleStateRepository::class,
            config('velm.persistence.module_state_repository', JsonModuleStateRepository::class)
        );
    }

    public function packageRegistered(): void
    {
        // Register Velm
        $this->app->make('velm')->register();
    }

    public function packageBooted(): void
    {
        $this->app->make('velm')->boot();
        if ($this->app->runningInConsole()) {
            $this->optimizes(
                optimize: 'velm:ide',
                clear: 'velm:clear-compiled'
            );
            // @phpstan-ignore-next-line
            if (method_exists($this, 'reloads')) {
                $this->reloads(reload: 'velm:ide');
            }
        }
    }

    public static function getCommands(): array
    {
        return [
            VelmClearCompiledCommand::class,
            VelmMakeCommand::class,
            VelmModuleInstallCommand::class,
            VelmIdeGeneratorCommand::class,
            ModelMakeCommand::class,
            PolicyMakeCommand::class,
            MigrateMakeCommand::class,
            VelmModuleMigrateCommand::class,
            VelmMigrateCommand::class,
        ];
    }

    public static function getMigrations(): array
    {
        return [
            'create_velm_modules_table',
        ];
    }
}
