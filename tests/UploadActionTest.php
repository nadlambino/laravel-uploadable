<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;

function upload_file_for(Model $model, array|UploadedFile|string|null $files = null, ?UploadOptions $options = null)
{
    if ($files === null) {
        $files = UploadedFile::fake()->image('avatar.jpg');
    }

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $model, $options);
}

beforeEach(function () {
    reset_config();
});

it('can upload a file for a given model', function () {
    $post = create_post(silently: true);

    upload_file_for($post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $post = create_post(model: new TestPostWithCustomFilename(), silently: true);

    upload_file_for($post, $file = UploadedFile::fake()->image('avatar.jpg'));

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $post = create_post(model: new TestPostWithCustomPath(), silently: true);

    upload_file_for($post);

    expect($post->uploads()->first()->path)->toContain('custom_path');
});

it('can upload a file and save the upload with additional data using the `beforeSavingUploadUsing` method', function () {
    $tag = fake()->word();
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) use ($tag) {
        $upload->tag = $tag;
    });
    $post = create_post(silently: true);

    upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    ));

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = create_post(silently: true);

    upload_file_for($post);

    expect($post->uploads()->first()->tag)->toBe($post->title);
});

it('should delete the uploaded files in the storage when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);

    /** @var Upload $action */
    $action = app(Upload::class);
    try_silently(fn () => $action->handle(UploadedFile::fake()->image('avatar.jpg'), $post, new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    )));

    $reflectionClass = new ReflectionClass(get_class($action));
    $property = $reflectionClass->getProperty('fullpaths');
    $property->setAccessible(true);
    $fullpaths = $property->getValue($action);

    expect($post->uploads()->count())->toBe(0);
    expect($fullpaths)->not->toBeEmpty();
    expect(Storage::exists($fullpaths[0]))->toBeFalse();
});

it('should not delete the recently created uploadable mdel when an error occurs', function () {
    config()->set('uploadable.delete_model_on_upload_fail', false);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    try_silently(fn () => upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    )));

    expect(TestPost::find($post->id))->not->toBeNull();
});

it('should delete the recently created uploadable model when an error occurs', function () {
    config()->set('uploadable.delete_model_on_upload_fail', true);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    try_silently(fn () => upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    )));

    expect(TestPost::find($post->id))->toBeNull();
});

it('should not rollback the updated uploadable model when an error occurs', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', false);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    $post = update_post($post->fresh(), ['title' => $newTitle = fake()->sentence()], silently: true);
    try_silently(fn () => upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    )));

    expect($post->title)->toBe($newTitle);
});

it('should rollback the updated uploadable model when an error occurs', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', true);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    $originalAttributes = $post->fresh()->getOriginal();
    $post = update_post($post, silently: true);
    try_silently(fn () => upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        originalAttributes: $originalAttributes
    )));

    expect($post->fresh()->title)->toBe($originalAttributes['title']);
    expect($post->fresh()->body)->toBe($originalAttributes['body']);
});

it('can upload a file using a string path of an uploaded file in the temporary disk', function () {
    $post = create_post(silently: true);
    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));
    upload_file_for($post, $path);

    expect($post->uploads()->first())->not->toBeNull();
});

it('should delete the file from the temporary disk after it was successfully uploaded', function () {
    $post = create_post(silently: true);
    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));
    upload_file_for($post, $path);

    expect(FacadesStorage::disk(config('uploadable.temporary_disk', 'local'))->exists($path))->toBeFalse();
});

it('should replace the previous uploaded file with the new one by setting it in the config', function () {
    config()->set('uploadable.replace_previous_uploads', true);
    $post = create_post(silently: true);

    $file = UploadedFile::fake()->image('avatar1.jpg');
    upload_file_for($post, $file);
    $oldUpload = $post->uploads()->first();

    $file = UploadedFile::fake()->image('avatar2.jpg');
    upload_file_for($post, $file, new UploadOptions());
    $newUpload = $post->uploads()->first();

    expect($post->uploads()->count())->toBe(1);
    expect($oldUpload->id)->not->toBe($newUpload->id);
    expect($newUpload->original_name)->toContain('avatar2.jpg');
});

it('should replace the previous uploaded file with the new one by setting it in the class', function () {
    // Emulate that this is set in config to false by default
    config()->set('uploadable.replace_previous_uploads', false);

    $post = create_post(silently: true);

    // Then set it to true via the class
    TestPost::replacePreviousUploads();
    $options = new UploadOptions(replacePreviousUploads: TestPost::$replacePreviousUploads);

    $file = UploadedFile::fake()->image('avatar1.jpg');
    upload_file_for($post, $file, $options);
    $oldUpload = $post->uploads()->first();

    $file = UploadedFile::fake()->image('avatar2.jpg');
    upload_file_for($post, $file, $options);
    $newUpload = $post->uploads()->first();

    expect($post->uploads()->count())->toBe(1);
    expect($oldUpload->id)->not->toBe($newUpload->id);
    expect($newUpload->original_name)->toContain('avatar2.jpg');
});

it('should not upload the file when the upload process is disabled', function () {
    TestPost::disableUpload();
    $post = create_post(silently: true);
    upload_file_for($post, options: new UploadOptions(disableUpload: TestPost::$disableUpload));

    expect($post->uploads()->count())->toBe(0);
});
