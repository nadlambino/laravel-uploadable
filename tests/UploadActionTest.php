<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

it('can upload a file for a given model', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    upload_file_for($post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $post = new TestPostWithCustomFilename();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    upload_file_for($post, $file = UploadedFile::fake()->image('avatar.jpg'));

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $post = new TestPostWithCustomPath();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

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
    $post->save();

    upload_file_for($post, options: new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback
    ));

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

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
    $post->save();

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
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
            deleteModelOnUploadFail: false
        ));
    } catch (\Exception) {
    }

    expect(TestPost::find($post->id))->not->toBeNull();
});

it('should delete the recently created uploadable model when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
            deleteModelOnUploadFail: true
        ));
    } catch (\Exception) {
    }

    expect(TestPost::find($post->id))->toBeNull();
});

it('should not rollback the updated uploadable model when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $post = TestPost::find($post->id);
    $post->title = $newTitle = fake()->sentence();
    $post->save();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
            rollbackModelOnUploadFail: false
        ));
    } catch (\Exception) {
    }

    $post = TestPost::find($post->id);

    expect($post->title)->toBe($newTitle);
});

it('should rollback the updated uploadable model when an error occurs', function () {
    TestPost::beforeSavingUploadUsing(function (ModelsUpload $upload) {
        throw new \Exception('An error occurred');
    });

    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $post = TestPost::find($post->id);
    $originalAttributes = $post->getOriginal();
    $post->title = $newTitle = fake()->sentence();
    $post->save();

    try {
        upload_file_for($post, options: new UploadOptions(
            beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
            rollbackModelOnUploadFail: true,
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
    $post->save();

    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));

    upload_file_for($post, $path);

    expect($post->uploads()->first())->not->toBeNull();
});

it('should delete the file from the temporary disk after it was successfully uploaded', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $file = UploadedFile::fake()->image('avatar.jpg');
    $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));

    upload_file_for($post, $path);

    expect(FacadesStorage::disk(config('uploadable.temporary_disk', 'local'))->exists($path))->toBeFalse();
});

it('should replace the previous file with the new one', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $file = UploadedFile::fake()->image('avatar1.jpg');
    upload_file_for($post, $file);

    $oldUpload = $post->uploads()->first();

    $file = UploadedFile::fake()->image('avatar2.jpg');
    upload_file_for($post, $file, new UploadOptions(replacePreviousUploads: true));

    $newUpload = $post->uploads()->first();

    expect($post->uploads()->count())->toBe(1);
    expect($oldUpload->id)->not->toBe($newUpload->id);
    expect($newUpload->original_name)->toContain('avatar2.jpg');
});

it('should matched the model\'s default options and the uploadable config', function () {
    expect(TestPost::$replacePreviousUploads)->toBe(config('uploadable.replace_previous_uploads'));
    expect(TestPost::$validateUploads)->toBe(config('uploadable.validate'));
    expect(TestPost::$uploadOnQueue)->toBe(config('uploadable.upload_on_queue'));
});

it('should not upload the file', function () {
    TestPost::dontUpload();
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    upload_file_for($post, options: new UploadOptions(dontUpload: TestPost::$dontUpload));

    expect($post->uploads()->count())->toBe(0);
});

it('can upload a single file from the request', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    app()->bind('request', fn() => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

it('can upload multiple files from the request', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
    ]);

    app()->bind('request', fn() => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(2);
});

it('should validate a single invalid image', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid images', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf')
        ],
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a singe invalid video', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'video' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid videos', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'videos' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf')
        ],
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a single invalid document', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'document' => UploadedFile::fake()->image('avatar1.jpg'),
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid documents', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'documents' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf')
        ],
    ]);

    app()->bind('request', fn() => $request);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should skip the validation for a specific uploadable model', function () {
    $post = new TestPost();
    $post::validateUploads(false);
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $request = new Request([
        'image' => UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    app()->bind('request', fn() => $request);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

// TODO: test the deletion and rollback of uploadable model when an error occurs on queue
