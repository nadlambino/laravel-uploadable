<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithSoftDeletes;

beforeEach(function () {
    reset_config();
});

it('can upload a file from request when the uploadable model is created', function () {
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);
});

it('can upload a file using the `uploadFrom` method when the uploadable model is created', function () {
    $post = new TestPost();
    $post->uploadFrom([
        UploadedFile::fake()->image('test.png'),
    ]);
    $post = create_post($post);

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);
});

it('can manually create the uploaded file using the `createUploads` method', function () {
    TestPost::disableUpload();
    $post = create_post();

    expect($post->uploads()->count())->toBe(0);

    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ])->createUploads();

    expect($post->uploads()->count())->toBe(1);
    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
});

it('should rollback the recently created uploadable model when the upload process from created event fails', function () {
    config()->set('uploadable.delete_model_on_upload_fail', true);
    create_request_with_files();

    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = new TestPost();
    try_silently(fn () => $post = create_post($post));

    expect($post->exists())->toBeFalse();
});

it('can upload a file from the request when the uploadable model is updated', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
    ]);
    $post = create_post();

    expect($post->uploads()->count())->toBe(1);

    create_request_with_files([
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    $post = update_post($post, ['title' => $newTitle = fake()->sentence()]);
    $uploads = $post->uploads()->get();

    expect($post->uploads()->count())->toBe(2);
    expect(Storage::exists($uploads[0]->path))->toBeTrue();
    expect(Storage::exists($uploads[1]->path))->toBeTrue();
    expect($uploads[0]->original_name)->toContain('avatar1.jpg');
    expect($uploads[1]->original_name)->toContain('avatar2.jpg');
    expect($post->title)->toBe($newTitle);
});

it('can upload an additional file from request when the uploadable model is updated', function () {
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request_with_files();
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(2);
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
    $post = create_post($post);

    expect($post->uploads()->count())->toBe(1);

    TestPost::disableUpload();
    TestPost::replacePreviousUploads();
    $post = update_post($post, ['title' => $newTitle = fake()->sentence()]);
    $post->uploadFrom([
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ])->updateUploads();

    expect($post->uploads()->count())->toBe(1);
    expect($newTitle)->toBe($post->title);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('can upload an additional file using the `uploadFrom` method when the uploadable model is updated', function () {
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request();

    $post->uploadFrom([
        UploadedFile::fake()->image('test.png'),
        UploadedFile::fake()->image('test2.png'),
    ]);
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(3);
});

it('should replace the previous uploaded files with new one when the uploadable model is updated, set from the config', function () {
    config()->set('uploadable.replace_previous_uploads', true);
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request_with_files([
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('should replace the previous uploaded files with new one when the uploadable model is updated, set from the class', function () {
    // Emulate that this is set in config to false by default
    config()->set('uploadable.replace_previous_uploads', false);
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request_with_files([
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);
    $post::replacePreviousUploads();
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->original_name)->toContain('avatar2.jpg');
});

it('should not replace the previous uploaded files when the uploadable model is updated, set from the config', function () {
    config()->set('uploadable.replace_previous_uploads', false);
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request_with_files([
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(2);
});

it('should not replace the previous uploaded files when the uploadable model is updated, set from the class', function () {
    // Emulate that this is set in config to true by default
    config()->set('uploadable.replace_previous_uploads', true);
    create_request_with_files();
    $post = create_post();

    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);

    create_request_with_files([
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);
    $post::replacePreviousUploads(false);
    $post = update_post($post);

    expect($post->uploads()->count())->toBe(2);
});

it('should rollback the recently updated uploadable model when the upload process from updated event fails', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', true);
    create_request_with_files();
    $post = create_post();

    create_request_with_files();
    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    try {
        $post->fresh()->update([
            'title' => $newTitle = fake()->sentence(),
        ]);
    } catch (\Throwable) {}

    expect($post->title)->not->toBe($newTitle);
    expect($post->uploads()->count())->toBe(1);
});

it('should not rollback the recently updated uploadable model when the upload process from updated event fails', function () {
    config()->set('uploadable.rollback_model_on_upload_fail', false);
    create_request_with_files();
    $post = create_post();

    create_request_with_files();
    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    try {
        $post->fresh()->update([
            'title' => $newTitle = fake()->sentence(),
        ]);
    } catch (\Throwable) {}

    expect($post->fresh()->title)->toBe($newTitle);
    expect($post->uploads()->count())->toBe(1);
});

it('should delete the files from storage and uploads table when the uploadable model was deleted', function () {
    config()->set('uploadable.force_delete_uploads', true);
    create_request_with_files();
    $post = create_post();
    $files = $post->uploads()->first();

    $post->delete();

    expect(Upload::query()->withTrashed()->get())->toBeEmpty();
    expect(Storage::exists($files->path))->toBeFalse();
});

it('should not delete the files from storage and uploads table when the uploadable model was deleted', function () {
    config()->set('uploadable.force_delete_uploads', false);
    create_request_with_files();
    $post = create_post();
    $files = $post->uploads()->first();

    $post->delete();

    expect(Upload::query()->withTrashed()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});

it('should soft delete the uploads when the uploadable model was just soft-deleted', function () {
    config()->set('uploadable.force_delete_uploads', false);
    create_request_with_files();
    $post = create_post(new TestPostWithSoftDeletes());
    $files = $post->uploads()->first();

    $post->delete();

    expect(Upload::query()->withTrashed()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});

it('should restore the uploads when the uploadable model was restored', function () {
    config()->set('uploadable.force_delete_uploads', false);
    create_request_with_files();
    $post = create_post(new TestPostWithSoftDeletes());
    $files = $post->uploads()->first();

    $post->delete();
    $post->restore();

    expect(Upload::query()->get())->not->toBeEmpty();
    expect(Storage::exists($files->path))->toBeTrue();
});