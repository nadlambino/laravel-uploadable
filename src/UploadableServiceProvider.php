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

        $this->publishes([
            __DIR__.'/Models/Upload.php' => app_path('Models/Upload.php'),
        ], 'uploadable-model');

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
            ->hasMigration('create_uploads_table')
            ->hasMigration('add_new_columns_to_uploads_table')
            ->hasRoute('uploadable')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->publishConfigFile()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }
}
