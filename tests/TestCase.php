<?php

namespace NadLambino\Uploadable\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use NadLambino\Uploadable\UploadableServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NadLambino\\Uploadable\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function setUpDatabase(Application $app): void
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            UploadableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/0001_01_01_000000_create_uploads_table.php.stub';
        $migration->up();

        $this->setUpAdditionalMigration();
    }

    private function setUpAdditionalMigration()
    {
        $migration = new class extends Migration
        {
            public function up()
            {
                Schema::table('uploads', function (Blueprint $table) {
                    $table->string('tag')->nullable()->default(null)->after('size');
                });
            }

            public function down()
            {
                Schema::table('uploads', function (Blueprint $table) {
                    $table->dropColumn('tag');
                });
            }
        };

        $migration->up();
    }
}
