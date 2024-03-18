<?php

namespace NadLambino\Uploadable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use NadLambino\Uploadable\Models\Traits\HasFile;
use NadLambino\Uploadable\Observers\UploadableObserver;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UploadableServiceProvider extends PackageServiceProvider
{
    public function boot() : void
    {
        parent::boot()
            ->bindConfig()
            ->bindService()
            ->observeModel();
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
        $this->app->bind('uploadable', function () {
            return (object) config('uploadable.env.'.app()->environment());
        });

        return $this;
    }

    protected function bindService(): static
    {
        $this->app->bind(\NadLambino\Uploadable\Contracts\Uploadable::class, function ($app) {
            return new Uploadable(Storage::disk($app['uploadable']->disk));
        });

        $this->app->bind(Uploadable::class, function ($app) {
            return new Uploadable(Storage::disk($app['uploadable']->disk));
        });

        return $this;
    }

    protected function observeModel(): static
    {
        Event::listen('eloquent.booted: *', function ($eventName, array $data) {
            /** @var Model $model */
            $model = $data[0];

            if (in_array(HasFile::class, class_uses_recursive($model))) {
                $model::observe(UploadableObserver::class);
            }
        });

        return $this;
    }
}
