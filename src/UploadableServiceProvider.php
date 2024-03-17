<?php

namespace NadLambino\Uploadable;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UploadableServiceProvider extends PackageServiceProvider
{
    public function boot() : void
    {
        parent::boot();

        $this->app->bind('uploadable', function () {
            return (object) config('uploadable.env.'.app()->environment());
        });

        $this->app->bind(\NadLambino\Uploadable\Contracts\Uploadable::class, function ($app) {
            return new Uploadable(Storage::disk($app['uploadable']->disk));
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('uploadable')
            ->runsMigrations()
            ->hasConfigFile()
            ->hasMigration('create_uploads_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }
}
