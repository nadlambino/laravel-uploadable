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
        parent::boot()
            ->bindConfig()
            ->bindService();
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

    protected function bindConfig(): static
    {
        $this->app->singleton(UploadableConfig::class, function () {
            return new UploadableConfig(
                disk: config('uploadable.disks.'.app()->environment().'.disk'),
                path: config('uploadable.disks.'.app()->environment().'.path'),
                host: config('uploadable.disks.'.app()->environment().'.host'),
            );
        });

        return $this;
    }

    protected function bindService(): static
    {
        $this->app->bind(\NadLambino\Uploadable\Contracts\Uploadable::class, function () {
            return new Uploadable(Storage::disk(UploadableConfig::instance()->disk));
        });

        $this->app->bind(Uploadable::class, function () {
            return new Uploadable(Storage::disk(UploadableConfig::instance()->disk));
        });

        return $this;
    }
}
