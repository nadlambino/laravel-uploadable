<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;

function uploadFileFor(Model $model, array|UploadedFile|null $files = null, array $options = [])
{
    if ($files === null) {
        $files = UploadedFile::fake()->image('avatar.jpg');
    }

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $model, $options);
}

it('can upload a file for a given model', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $post = new TestPostWithCustomFilename();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post, $file = UploadedFile::fake()->image('avatar.jpg'));

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $post = new TestPostWithCustomPath();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post);

    expect($post->uploads()->first()->path)->toContain('custom_path');
});

it('can upload a file and save the upload with additional data using the `beforeSavingUploadUsing` method', function () {
    $tag = fake()->word();
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) use ($tag) {
        $upload->tag = $tag;
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post, options: ['before_saving_upload_using' => TestPost::$beforeSavingUploadCallback]);

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post);

    expect($post->uploads()->first()->tag)->toBe($post->title);
});

it('should delete the uploaded files in the storage when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    /** @var Upload $action */
    $action = app(Upload::class);
    try {
        $action->handle(UploadedFile::fake()->image('avatar.jpg'), $post, ['before_saving_upload_using' => TestPost::$beforeSavingUploadCallback]);
    } catch (\Exception) {}

    $reflectionClass = new ReflectionClass(get_class($action));
    $property = $reflectionClass->getProperty('fullpaths');
    $property->setAccessible(true);
    $fullpaths = $property->getValue($action);

    expect($post->uploads()->count())->toBe(0);
    expect($fullpaths)->not->toBeEmpty();
    expect(Storage::exists($fullpaths[0]))->toBeFalse();
});
