<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;
use NadLambino\Uploadable\Tests\Models\TestPostWithSoftDeletes;

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

// TODO: Move the rest of the tests into their respective files

it('should delete the files from storage when the uploadable model was deleted', function () {
    config()->set('uploadable.force_delete_uploads', true);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $post, $options);

    $files = $post->uploads()->first();

    $post->delete();

    expect(ModelsUpload::query()->withTrashed()->get())->toBeEmpty();
    expect(Storage::exists($files->path))->toBeFalse();
});

it('should not delete the files from storage when the uploadable model was deleted', function () {
    config()->set('uploadable.force_delete_uploads', false);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $post, $options);

    $files = $post->uploads()->first();

    $post->delete();

    expect(ModelsUpload::query()->withTrashed()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});

it('should soft deletes the uploads when the uploadable model was just soft-deleted', function () {
    config()->set('uploadable.force_delete_uploads', false);

    $post = new TestPostWithSoftDeletes();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $post, $options);

    $files = $post->uploads()->first();

    $post->delete();

    expect(ModelsUpload::query()->withTrashed()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});

it('should restore the uploads when the uploadable model was restored', function () {
    config()->set('uploadable.force_delete_uploads', false);

    $post = new TestPostWithSoftDeletes();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($files, $post, $options);

    $files = $post->uploads()->first();

    $post->delete();
    $post->restore();

    expect(ModelsUpload::query()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});

it('can upload a file from request when the uploadable model is created', function () {
    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);
});

it('can upload a outside of request when the uploadable model is updated', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(3);
});

it('should rollback the recently created uploadable model when the upload process from created event fails', function () {
    config()->set('uploadable.delete_model_on_upload_fail', true);

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    try {
        $post->save();
    } catch (\Exception) {

    }

    expect($post->exists())->toBeFalse();
});

it('can upload a file from the request when the uploadable model is updated', function () {
    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->uploads()->count())->toBe(1);

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post->update([
        'title' => $newTitle = fake()->sentence(),
    ]);

    $uploads = $post->uploads()->get();
    expect($post->uploads()->count())->toBe(2);
    expect(Storage::exists($uploads[0]->path))->toBeTrue();
    expect(Storage::exists($uploads[1]->path))->toBeTrue();
    expect($uploads[0]->original_name)->toContain('avatar1.jpg');
    expect($uploads[1]->original_name)->toContain('avatar2.jpg');
    expect($post->title)->toBe($newTitle);
});

it('should rollback the recently updated uploadable model when the upload process from updated event fails, set from the config', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', true);

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = TestPost::find($post->id);
    $post->title = $newTitle = fake()->sentence();
    try {
        $post->save();
    } catch (\Exception) {

    }

    $post = TestPost::find($post->id);

    expect($post->title)->not->toBe($newTitle);
    expect($post->uploads()->count())->toBe(1);
});

it('should replace the previous file with the new one, set from the config', function () {
    config()->set('uploadable.replace_previous_uploads', true);

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = TestPost::find($post->id);
    $post->title = fake()->sentence();
    $post->save();

    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('should replace the previous file with the new one, set from the class', function () {
    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    TestPost::replacePreviousUploads();
    $post = TestPost::find($post->id);
    $post->title = fake()->sentence();
    $post->save();

    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('can manually create the uploaded file using the `createUploads` method', function () {
    TestPost::disableUpload();
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->uploads()->count())->toBe(0);

    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ])->createUploads();

    expect($post->uploads()->count())->toBe(1);
    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
});

it('can manually create the uploaded file using the `updateUploads` method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->uploads()->count())->toBe(1);

    TestPost::disableUpload();

    $post->update([
        'title' => $newTitle = fake()->sentence(),
    ]);
    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ])->updateUploads();

    expect($post->uploads()->count())->toBe(2);
    expect($newTitle)->toBe($post->title);
});

it('can manually create the uploaded file using the `updateUploads` method and replace previous uploads', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->uploads()->count())->toBe(1);

    TestPost::disableUpload();
    TestPost::replacePreviousUploads();
    $post->update([
        'title' => $newTitle = fake()->sentence(),
    ]);
    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ])->updateUploads();

    expect($post->uploads()->count())->toBe(1);
    expect($newTitle)->toBe($post->title);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('should upload a file on queue when set from the class', function () {
    Queue::fake();
    config()->set('uploadable.upload_on_queue', null);

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    TestPost::uploadOnQueue('default');
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    Queue::assertPushedOn('default', ProcessUploadJob::class);
    Queue::assertPushed(ProcessUploadJob::class, function (ProcessUploadJob $job) use ($post) {
        return $job->model->id === $post->id;
    });
    Queue::assertPushed(ProcessUploadJob::class, function (ProcessUploadJob $job) {
        $job->handle();

        return true;
    });

    expect($post->uploads()->count())->toBe(1);
    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
});

it('should return only one image when querying the `image` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->image()->get()->count())->toBe(1);
    expect($post->image->original_name)->toContain('avatar1.jpg');
});

it('should return only the images when querying the `images` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->images()->get()->count())->toBe(2);
    expect($post->images->count())->toBe(2);
});

it('should return only one video when querying the `video` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'videos' => [
            UploadedFile::fake()->create('video1.mp4', 1000, 'video/mp4'),
            UploadedFile::fake()->create('video2.mp4', 1000, 'video/mp4'),
        ],
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->video()->get()->count())->toBe(1);
    expect($post->video->original_name)->toContain('video1.mp4');
});

it('should return only the videos when querying the `videos` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'videos' => [
            UploadedFile::fake()->create('video1.mp4', 1000, 'video/mp4'),
            UploadedFile::fake()->create('video2.mp4', 1000, 'video/mp4'),
        ],
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->videos()->get()->count())->toBe(2);
    expect($post->videos->count())->toBe(2);
});

it('should return only one document when querying the `document` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'document' => [
            UploadedFile::fake()->create('document1.pdf', 1000, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1000, 'application/pdf'),
        ],
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->document()->get()->count())->toBe(1);
    expect($post->document->original_name)->toContain('document1.pdf');
});

it('should return only the documents when querying the `documents` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'document' => [
            UploadedFile::fake()->create('document1.pdf', 1000, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1000, 'application/pdf'),
        ],
    ]);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    expect($post->documents()->get()->count())->toBe(2);
    expect($post->documents->count())->toBe(2);
});
