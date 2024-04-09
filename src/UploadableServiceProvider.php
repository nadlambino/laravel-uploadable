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
        parent::boot()->bindService();
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

    protected function bindService(): static
    {
        $default = config('filesystems.default', 'public');

        $this->app->bind(
            \NadLambino\Uploadable\Contracts\Uploadable::class,
            fn () => new Uploadable(Storage::disk($default))
        );

        $this->app->bind(
            Uploadable::class,
            fn () => new Uploadable(Storage::disk($default))
        );

        return $this;
    }
}
