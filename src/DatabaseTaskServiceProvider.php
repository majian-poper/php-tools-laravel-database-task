<?php

namespace PHPTools\LaravelDatabaseTask;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatabaseTaskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-database-task')
            ->hasConfigFile()
            ->hasTranslations()
            ->discoversMigrations();
    }
}
