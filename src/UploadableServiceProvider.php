<?php

namespace NadLambino\Uploadable;

use Illuminate\Support\Facades\Storage;
use NadLambino\Uploadable\Contracts\StorageContract;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UploadableServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        parent::boot();

        $default = config('filesystems.default', 'public');
        $this->app->bind(StorageContract::class, fn () => new StorageService(Storage::disk($default)));
        $this->app->bind(StorageService::class, fn () => new StorageService(Storage::disk($default)));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('uploadable')
            ->runsMigrations()
            ->hasConfigFile()
            ->hasMigration('0001_01_01_000000_create_uploads_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }
}
