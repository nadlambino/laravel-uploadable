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
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomRules;
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

afterEach(function () {
    config()->set('uploadable.validate', true);
    config()->set('uploadable.delete_model_on_upload_fail', true);
    config()->set('uploadable.rollback_model_on_upload_fail', true);
    config()->set('uploadable.force_delete_uploads', false);
    config()->set('uploadable.replace_previous_uploads', false);
    config()->set('uploadable.upload_on_queue', null);
    config()->set('uploadable.delete_model_on_queue_upload_fail', false);
    config()->set('uploadable.rollback_model_on_queue_upload_fail', false);
    config()->set('uploadable.temporary_disk', 'local');
    TestPost::$beforeSavingUploadCallback = null;
    TestPost::$disableUpload = false;
    TestPost::$replacePreviousUploads = false;
    TestPost::$uploadOnQueue = null;
    TestPost::$validateUploads = true;
});

it('can upload a file for a given model', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    upload_file_for($post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $post = new TestPostWithCustomFilename();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    upload_file_for($post, $file = UploadedFile::fake()->image('avatar.jpg'));

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $post = new TestPostWithCustomPath();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    upload_file_for($post);

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
    $post->saveQuietly();

    upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    ));

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    upload_file_for($post);

    expect($post->uploads()->first()->tag)->toBe($post->title);
});

it('should delete the uploaded files in the storage when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    /** @var Upload $action */
    $action = app(Upload::class);
    try {
        $action->handle(UploadedFile::fake()->image('avatar.jpg'), $post, new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
        ));
    } catch (\Exception) {
    }

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

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        ));
    } catch (\Exception) {
    }

    expect(TestPost::find($post->id))->not->toBeNull();
});

it('should delete the recently created uploadable model when an error occurs', function () {
    config()->set('uploadable.delete_model_on_upload_fail', true);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        ));
    } catch (\Exception) {
    }

    expect(TestPost::find($post->id))->toBeNull();
});

it('should not rollback the updated uploadable model when an error occurs', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', false);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $post = TestPost::find($post->id);
    $post->title = $newTitle = fake()->sentence();
    $post->saveQuietly();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        ));
    } catch (\Exception) {
    }

    $post = TestPost::find($post->id);

    expect($post->title)->toBe($newTitle);
});

it('should rollback the updated uploadable model when an error occurs', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', true);

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $post = TestPost::find($post->id);
    $originalAttributes = $post->getOriginal();
    $post->title = $newTitle = fake()->sentence();
    $post->saveQuietly();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
            originalAttributes: $originalAttributes
        ));
    } catch (\Exception) {
    }

    $post = TestPost::find($post->id);

    expect($post->title)->not->toBe($newTitle);
});

it('can upload a file that is uploaded in temporary disk first', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));

    upload_file_for($post, $path);

    expect($post->uploads()->first())->not->toBeNull();
});

it('should delete the file from the temporary disk after it was successfully uploaded', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));

    upload_file_for($post, $path);

    expect(FacadesStorage::disk(config('uploadable.temporary_disk', 'local'))->exists($path))->toBeFalse();
});

it('should replace the previous file with the new one by setting it on the config', function () {
    config()->set('uploadable.replace_previous_uploads', true);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

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

it('should replace the previous file with the new one by setting it from the class', function () {
    // Emulate that this is set in config to false by default
    config()->set('uploadable.replace_previous_uploads', false);

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

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

it('should not upload the file', function () {
    TestPost::disableUpload();
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    upload_file_for($post, options: new UploadOptions(disableUpload: TestPost::$disableUpload));

    expect($post->uploads()->count())->toBe(0);
});

it('can upload a single file from the request', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

it('can upload multiple files from the request', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(2);
});

it('should validate a single invalid image', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid images', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        ],
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a singe invalid video', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'video' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid videos', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'videos' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        ],
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a single invalid document', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'document' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid documents', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'documents' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
        ],
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should override the default validation rules and messages', function () {
    $post = new TestPostWithCustomRules();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.webp'),
    ]);

    app()->bind('request', fn () => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('Only jpeg, jpg and png files are allowed');

    $post->getUploads();
});

it('should skip the validation for a specific uploadable model', function () {
    $post = new TestPost();
    $post::validateUploads(false);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

it('can upload a file outside of the context of request', function () {
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
    $post->saveQuietly();

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(3);

    $uploads = $post->uploads()->get();

    expect(Storage::exists($uploads[0]->path))->toBeTrue();
    expect(Storage::exists($uploads[1]->path))->toBeTrue();
    expect(Storage::exists($uploads[2]->path))->toBeTrue();
});

it('can upload a file outside of the context of request using a string path from temporary disk', function () {
    $fullpath = UploadedFile::fake()->image('avatar.jpg')->store('tmp', config('uploadable.temporary_disk', 'local'));

    $post = new TestPost();
    $post->uploadFrom($fullpath);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
});

it('can upload a file on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');

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

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post) {
        return $job->files === $files && $job->model->id === $post->id;
    });
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        $job->handle($files, $post, $options);

        return true;
    });

    expect($post->uploads()->count())->toBe(1);
});

it('should delete the uploadable model when an error occurs during the upload process on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    config()->set('uploadable.delete_model_on_queue_upload_fail', true);
    config()->set('uploadable.upload_on_queue', 'default');

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try {
            $job->handle($files, $post, $options);
        } catch (\Exception) {

        }

        return true;
    });

    expect($post->exists())->toBeFalse();
});

it('should not delete the uploadable model when an error occurs during the upload process on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    config()->set('uploadable.delete_model_on_queue_upload_fail', false);
    config()->set('uploadable.upload_on_queue', 'default');

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try {
            $job->handle($files, $post, $options);
        } catch (\Exception) {

        }

        return true;
    });

    $post = TestPost::find($post->id);
    expect($post)->not->toBeNull();
});

it('should rollback the changes from uploadable model when an error occurs during the upload process on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    config()->set('uploadable.rollback_model_on_queue_upload_fail', true);
    config()->set('uploadable.upload_on_queue', 'default');

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $post = TestPost::find($post->id);
    $originalAttributes = $post->getOriginal();
    $post->title = $newTitle = fake()->sentence();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        originalAttributes: $originalAttributes,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try {
            $job->handle($files, $post, $options);
        } catch (\Exception) {

        }

        return true;
    });

    $post = TestPost::find($post->id);

    expect($post->title)->not->toBe($newTitle);
});

it('should not rollback the changes from uploadable model when an error occurs during the upload process on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    config()->set('uploadable.rollback_model_on_queue_upload_fail', false);
    config()->set('uploadable.upload_on_queue', 'default');

    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->saveQuietly();

    $post = TestPost::find($post->id);
    $post->title = $newTitle = fake()->sentence();
    $post->saveQuietly();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn () => $request);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try {
            $job->handle($files, $post, $options);
        } catch (\Exception) {

        }

        return true;
    });

    $post = TestPost::find($post->id);

    expect($post->title)->toBe($newTitle);
});

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
