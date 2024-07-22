<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomStorageOptions;
use NadLambino\Uploadable\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function reset_config(): void
{
    config()->set('uploadable.validate', true);
    config()->set('uploadable.delete_model_on_upload_fail', true);
    config()->set('uploadable.rollback_model_on_upload_fail', true);
    config()->set('uploadable.force_delete_uploads', false);
    config()->set('uploadable.replace_previous_uploads', false);
    config()->set('uploadable.upload_on_queue', null);
    config()->set('uploadable.delete_model_on_queue_upload_fail', false);
    config()->set('uploadable.rollback_model_on_queue_upload_fail', false);
    config()->set('uploadable.temporary_disk', 'local');
    config()->set('uploadable.temporary_url.expiration', '1 hour');
    config()->set('app.timezone', 'UTC');
    config()->set('filesystems.default', 'local');
    TestPost::$beforeSavingUploadCallback = null;
    TestPost::$disableUpload = false;
    TestPost::$replacePreviousUploads = null;
    TestPost::$uploadOnQueue = null;
    TestPost::$validateUploads = null;
    TestPost::$uploadDisk = null;
    TestPostWithCustomStorageOptions::$uploadStorageOptions = null;
    TestPostWithCustomStorageOptions::$uploadDisk = null;
    Upload::disableFor([]);
    Upload::onlyFor([]);
}

function create_post(?Model $model = null, array $attributes = [], bool $silently = false): Model
{
    $model = $model ?? new TestPost();
    $data = $attributes + [
        'title' => fake()->sentence(),
        'body' => fake()->paragraph(),
    ];

    match ($silently) {
        true => $model->forceFill($data)->saveQuietly(),
        false => $model->forceFill($data)->save(),
    };

    return $model;
}

function update_post(Model $model, array $attributes = [], bool $silently = false): Model
{
    $data = $attributes + [
        'title' => fake()->sentence(),
        'body' => fake()->paragraph(),
    ];

    match ($silently) {
        true => $model->forceFill($data)->saveQuietly(),
        false => $model->forceFill($data)->save(),
    };

    return $model->fresh();
}

function try_silently(\Closure $callback): void
{
    try {
        $callback();
    } catch (\Throwable) {
    }
}

function create_request_with_files(array $files = [], string $type = 'image'): Request
{
    $type = count($files) > 1
        ? str($type)->plural()->value()
        : str($type)->singular()->value();

    $files = empty($files) ? match ($type) {
        'image' || 'images' => UploadedFile::fake()->image('avatar.jpg'),
        'video' || 'videos' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
        'document' || 'documents' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        default => UploadedFile::fake()->image('avatar.jpg'),
    } : $files;

    $files = is_array($files) && count($files) === 1 ? $files[0] : $files;

    $request = new Request([$type => $files]);

    app()->bind('request', fn () => $request);

    return $request;
}

function create_request()
{
    $request = new Request();

    app()->bind('request', fn () => $request);

    return $request;
}

function invoke_private_method(object $object, string $method, ...$args): mixed
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($method);
    $method->setAccessible(true);

    return $method->invoke($object, ...$args);
}

function access_static_private_property(string $class, string $property): mixed
{
    $reflection = new ReflectionClass($class);
    $property = $reflection->getProperty($property);
    $property->setAccessible(true);

    return $property->getValue();
}
