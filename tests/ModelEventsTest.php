<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload as ActionsUpload;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomStorageOptions;
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

it('should not upload the file for a model that was added to the disabled list', function () {
    create_request_with_files();
    ActionsUpload::disableFor(TestPostWithCustomFilename::class);
    $post = create_post(new TestPost());
    $anotherPost = create_post(new TestPostWithCustomFilename());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(0);
});

it('should not upload the file for the models that were added to the disabled list', function () {
    create_request_with_files();
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPost());
    $anotherPost = create_post(new TestPostWithCustomFilename());
    $anotherPost2 = create_post(new TestPostWithCustomPath());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(0);
    expect($anotherPost2->uploads()->count())->toBe(0);
});

it('should upload the file for the model that was previously added to the disabled list but then enabled', function () {
    create_request_with_files();
    ActionsUpload::disableFor(TestPostWithCustomFilename::class);
    $post = create_post(new TestPost());
    ActionsUpload::enableFor(TestPostWithCustomFilename::class);
    $anotherPost = create_post(new TestPostWithCustomFilename());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
});

it('should upload the file for the model that was previously added to the disabled list including with other models but then enabled', function () {
    create_request_with_files();
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPost());
    ActionsUpload::enableFor(TestPostWithCustomFilename::class);
    $anotherPost = create_post(new TestPostWithCustomFilename());
    $anotherPost2 = create_post(new TestPostWithCustomPath());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost2->uploads()->count())->toBe(0);
});

it('should upload the file for the models that were previously added to the disabled list but then enabled', function () {
    create_request_with_files();
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPost());
    ActionsUpload::enableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $anotherPost = create_post(new TestPostWithCustomFilename());
    $anotherPost2 = create_post(new TestPostWithCustomPath());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost2->uploads()->count())->toBe(1);
});

it('should accept a model instance when disabling the upload process', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post = create_post(new TestPost(), silently: true);
    ActionsUpload::disableFor($post);
    $updatePost = update_post($post);
    $anotherPost = create_post(new TestPost());

    expect($updatePost->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
});

it('should only disable the upload for the specific model instance', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), silently: true);
    $post2 = create_post(new TestPost(), silently: true);
    ActionsUpload::disableFor($post1);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);

    expect($updatePost1->uploads()->count())->toBe(0);
    expect($updatePost2->uploads()->count())->toBe(1);
});

it('should accept a model instance when enabling the upload process', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post = create_post(new TestPost(), silently: true);
    ActionsUpload::disableFor($post);
    ActionsUpload::enableFor($post);
    $updatePost = update_post($post);
    $anotherPost = create_post(new TestPost());

    expect($updatePost->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
});

it('should only enable the upload for the specific model instance', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), ['title' => 'Post 1'], silently: true);
    $post2 = create_post(new TestPost(), ['title' => 'Post 2'], silently: true);
    ActionsUpload::disableFor([$post1, $post2]);
    ActionsUpload::enableFor($post1);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);

    expect($updatePost1->uploads()->count())->toBe(1);
    expect($updatePost2->uploads()->count())->toBe(0);
});

it('should upload the file only for the given model class', function () {
    create_request_with_files();
    ActionsUpload::onlyFor(TestPostWithCustomFilename::class);
    $post = create_post(new TestPost());
    $anotherPost = create_post(new TestPostWithCustomFilename());

    expect($post->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
});

it('should upload the file only for the given model classes', function () {
    create_request_with_files();
    ActionsUpload::onlyFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPost());
    $anotherPost = create_post(new TestPostWithCustomFilename());
    $anotherPost2 = create_post(new TestPostWithCustomPath());

    expect($post->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost2->uploads()->count())->toBe(1);
});

it('should upload the file only for the given model instance', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post = create_post(new TestPost(), silently: true);
    ActionsUpload::onlyFor($post);
    $anotherPost = create_post(new TestPostWithCustomFilename());
    $updatePost = update_post($post);

    expect($updatePost->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(0);
});

it('should upload the file only for the given model instances', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), ['title' => 'Post 1'], silently: true);
    $post2 = create_post(new TestPost(), ['title' => 'Post 2'], silently: true);
    ActionsUpload::onlyFor([$post1, $post2]);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);
    $post3 = create_post(new TestPost());

    expect($updatePost1->uploads()->count())->toBe(1);
    expect($updatePost2->uploads()->count())->toBe(1);
    expect($post3->uploads()->count())->toBe(0);
});

it('should upload the file only for the given model instance and class', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), ['title' => 'Post 1'], silently: true);
    $post2 = create_post(new TestPost(), ['title' => 'Post 2'], silently: true);
    ActionsUpload::onlyFor([$post1, TestPostWithCustomFilename::class]);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);
    $anotherPost = create_post(new TestPostWithCustomFilename());

    expect($updatePost1->uploads()->count())->toBe(1);
    expect($updatePost2->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
});

it('should remove the model class from the disabled list when onlyFor method is used', function () {
    create_request_with_files();
    ActionsUpload::disableFor(TestPostWithCustomFilename::class);
    ActionsUpload::onlyFor(TestPostWithCustomFilename::class);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(0);
    expect(access_static_private_property(ActionsUpload::class, 'disabledModels'))->toBeEmpty();
});

it('should remove the model instance from the disabled list when onlyFor method is used', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post = create_post(new TestPost(), silently: true);
    ActionsUpload::disableFor($post);
    ActionsUpload::onlyFor($post);
    $updatePost = update_post($post);
    $anotherPost = create_post(new TestPost());

    expect($updatePost->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(0);
    expect(access_static_private_property(ActionsUpload::class, 'disabledModels'))->toBeEmpty();
});

it('should remove the model classes from the disabled list when onlyFor method is used', function () {
    create_request_with_files();
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    ActionsUpload::onlyFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPostWithCustomPath());
    $anotherPost2 = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost2->uploads()->count())->toBe(0);
    expect(access_static_private_property(ActionsUpload::class, 'disabledModels'))->toBeEmpty();
});

it('should remove the model instances from the disabled list when onlyFor method is used', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), ['title' => 'Post 1'], silently: true);
    $post2 = create_post(new TestPost(), ['title' => 'Post 2'], silently: true);
    ActionsUpload::disableFor([$post1, $post2]);
    ActionsUpload::onlyFor([$post1, $post2]);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);
    $post3 = create_post(new TestPost());

    expect($updatePost1->uploads()->count())->toBe(1);
    expect($updatePost2->uploads()->count())->toBe(1);
    expect($post3->uploads()->count())->toBe(0);
    expect(access_static_private_property(ActionsUpload::class, 'disabledModels'))->toBeEmpty();
});

it('should remove both model class and instance from the disabled list when onlyFor method is used', function () {
    create_request_with_files();
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    ActionsUpload::onlyFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPostWithCustomPath());
    $anotherPost2 = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost2->uploads()->count())->toBe(0);
    expect(access_static_private_property(ActionsUpload::class, 'disabledModels'))->toBeEmpty();
});

it('should remove the model class from the enabled models when disableFor method is used', function () {
    create_request_with_files();
    ActionsUpload::onlyFor(TestPostWithCustomFilename::class);
    ActionsUpload::disableFor(TestPostWithCustomFilename::class);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect(access_static_private_property(ActionsUpload::class, 'onlyModels'))->toBeEmpty();
});

it('should remove the model instance from the enabled models when disableFor method is used', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post = create_post(new TestPost(), silently: true);
    ActionsUpload::onlyFor($post);
    ActionsUpload::disableFor($post);
    $updatePost = update_post($post);
    $anotherPost = create_post(new TestPost());

    expect($updatePost->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(1);
    expect(access_static_private_property(ActionsUpload::class, 'onlyModels'))->toBeEmpty();
});

it('should remove the model classes from the enabled models when disableFor method is used', function () {
    create_request_with_files();
    ActionsUpload::onlyFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPostWithCustomPath());
    $anotherPost2 = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(0);
    expect($anotherPost2->uploads()->count())->toBe(1);
    expect(access_static_private_property(ActionsUpload::class, 'onlyModels'))->toBeEmpty();
});

it('should remove the model instances from the enabled models when disableFor method is used', function () {
    create_request_with_files();
    // Create a post silently so it won't have the uploads initially
    $post1 = create_post(new TestPost(), ['title' => 'Post 1'], silently: true);
    $post2 = create_post(new TestPost(), ['title' => 'Post 2'], silently: true);
    ActionsUpload::onlyFor([$post1, $post2]);
    ActionsUpload::disableFor([$post1, $post2]);
    $updatePost1 = update_post($post1);
    $updatePost2 = update_post($post2);
    $post3 = create_post(new TestPost());

    expect($updatePost1->uploads()->count())->toBe(0);
    expect($updatePost2->uploads()->count())->toBe(0);
    expect($post3->uploads()->count())->toBe(1);
    expect(access_static_private_property(ActionsUpload::class, 'onlyModels'))->toBeEmpty();
});

it('should remove both model class and instance from the enabled models when disableFor method is used', function () {
    create_request_with_files();
    ActionsUpload::onlyFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    ActionsUpload::disableFor([TestPostWithCustomFilename::class, TestPostWithCustomPath::class]);
    $post = create_post(new TestPostWithCustomFilename());
    $anotherPost = create_post(new TestPostWithCustomPath());
    $anotherPost2 = create_post(new TestPost());

    expect($post->uploads()->count())->toBe(0);
    expect($anotherPost->uploads()->count())->toBe(0);
    expect($anotherPost2->uploads()->count())->toBe(1);
    expect(access_static_private_property(ActionsUpload::class, 'onlyModels'))->toBeEmpty();
});

it('can upload a file with storage options, set from static', function () {
    create_request_with_files();
    TestPost::uploadStorageOptions([
        'visibility' => 'private',
    ]);
    $post = create_post();

    expect(invoke_private_method($post, 'getStorageOptions'))->toBe([
        'visibility' => 'private',
    ]);
});

it('can upload a file with storage options, set from the class', function () {
    create_request_with_files();
    $post = create_post(new TestPostWithCustomStorageOptions());

    expect(invoke_private_method($post, 'getStorageOptions'))->toBe([
        'visibility' => 'public',
    ]);
});

it('should overried the storage options set from the class when a new option is set statically', function () {
    create_request_with_files();
    TestPostWithCustomStorageOptions::uploadStorageOptions([
        'visibility' => 'private',
    ]);
    $post = create_post(new TestPostWithCustomStorageOptions());

    expect(invoke_private_method($post, 'getStorageOptions'))->toBe([
        'visibility' => 'private',
    ]);
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
    } catch (\Throwable) {
    }

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
    } catch (\Throwable) {
    }

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

it('can upload a file to a different disk', function () {
    config()->set('filesystems.default', 'local');
    create_request_with_files();
    TestPost::uploadDisk('public');
    $post = create_post(new TestPost());

    expect(Storage::disk('public')->exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->disk)->toBe('public');
});

it('should still upload the file from the default disk when a new disk is set from different model', function () {
    config()->set('filesystems.default', 'local');
    create_request_with_files();
    TestPostWithCustomStorageOptions::uploadDisk('public');
    $post = create_post(new TestPostWithCustomStorageOptions());
    $anotherPost = create_post(new TestPost());

    expect(Storage::disk('public')->exists($post->uploads()->first()->path))->toBeTrue();
    expect($post->uploads()->count())->toBe(1);
    expect($post->uploads()->first()->disk)->toBe('public');

    expect(Storage::disk('local')->exists($anotherPost->uploads()->first()->path))->toBeTrue();
    expect($anotherPost->uploads()->count())->toBe(1);
    expect($anotherPost->uploads()->first()->disk)->toBe('local');
});

it('can set the value of collection column', function () {
    create_request_with_files();
    $collection = fake()->word();
    TestPost::beforeSavingUploadUsing(function (Upload $upload) use ($collection) {
        $upload->collection = $collection;
    });
    $post = create_post(new TestPost());

    expect($post->uploads()->first()->collection)->toBe($collection);
});
