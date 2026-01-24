<?php

namespace Velm\Core;

use Illuminate\Console\Command;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Velm\Core\Persistence\Contracts\ModuleStateRepository;
use Velm\Core\Persistence\Eloquent\EloquentModuleStateRepository;
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
        $this->app->bind(
            ModuleStateRepository::class,
            config('velm.persistence.module_state_repository', EloquentModuleStateRepository::class)
        );

    }

    public function packageRegistered()
    {
        // Register Velm
        $this->app->make('velm')->register();
    }

    public function packageBooted()
    {
        $this->app->make('velm')->boot();
    }

    public static function getCommands(): array
    {
        return [];
    }

    public static function getMigrations(): array
    {
        return [
            'create_velm_modules_table',
        ];
    }
}
