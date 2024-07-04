<?php

use Illuminate\Support\Facades\Queue;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Tests\Models\TestPost;

beforeEach(function () {
    reset_config();
});


it('can process the file upload in the queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    create_request_with_files();

    $post = create_post(silently: true);

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
    create_request_with_files();

    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try_silently(fn () => $job->handle($files, $post, $options));

        return true;
    });

    expect($post->exists())->toBeFalse();
});

it('should not delete the uploadable model when an error occurs during the upload process on queue', function () {
    Queue::fake();
    config()->set('queues.default', 'sync');
    config()->set('uploadable.delete_model_on_queue_upload_fail', false);
    config()->set('uploadable.upload_on_queue', 'default');
    create_request_with_files();

    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);

    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try_silently(fn () => $job->handle($files, $post, $options));

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
    create_request_with_files();

    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    $originalAttributes = $post->getOriginal();
    $post = update_post($post, ['title' => $newTitle = fake()->sentence()], silently: true);
    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
        originalAttributes: $originalAttributes,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try_silently(fn () => $job->handle($files, $post, $options));

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
    create_request_with_files();

    TestPost::beforeSavingUploadUsing(function (Upload $upload) {
        throw new \Exception('An error occurred');
    });
    $post = create_post(silently: true);
    $post = update_post($post, ['title' => $newTitle = fake()->sentence()], silently: true);
    $files = $post->getUploads();

    $options = new UploadOptions(
        beforeSavingUploadUsing: TestPost::$beforeSavingUploadCallback,
    );

    ProcessUploadJob::dispatch($files, $post, $options);
    Queue::assertPushed(ProcessUploadJob::class, function ($job) use ($files, $post, $options) {
        try_silently(fn () => $job->handle($files, $post, $options));

        return true;
    });

    $post = TestPost::find($post->id);

    expect($post->title)->toBe($newTitle);
});
