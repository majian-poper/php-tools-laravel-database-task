<?php

namespace PHPTools\LaravelDatabaseTask;

use PHPTools\LaravelDatabaseTask\Commands\RunScheduledCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatabaseTaskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-database-task')
            ->hasCommand(RunScheduledCommand::class)
            ->hasConfigFile()
            ->hasTranslations()
            ->discoversMigrations();
    }
}
