<?php

namespace NadLambino\Uploadable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use NadLambino\Uploadable\Commands\UploadableCommand;

class UploadableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('uploadable')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_uploadable_table')
            ->hasCommand(UploadableCommand::class);
    }
}
