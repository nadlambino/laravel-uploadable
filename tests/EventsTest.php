<?php

use Illuminate\Support\Facades\Event;
use NadLambino\Uploadable\Events\AfterUpload;
use NadLambino\Uploadable\Events\BeforeUpload;
use NadLambino\Uploadable\Events\CompleteUpload;
use NadLambino\Uploadable\Events\FailedUpload;
use NadLambino\Uploadable\Events\StartUpload;
use NadLambino\Uploadable\Tests\Listeners\AfterUpload as ListenersAfterUpload;
use NadLambino\Uploadable\Tests\Listeners\BeforeUpload as ListenersBeforeUpload;
use NadLambino\Uploadable\Tests\Listeners\CompleteUpload as ListenersCompleteUpload;
use NadLambino\Uploadable\Tests\Listeners\FailedUpload as ListenersFailedUpload;
use NadLambino\Uploadable\Tests\Listeners\StartUpload as ListenersStartUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;

beforeEach(function () {
    reset_config();
});

it('should dispatch the UploadStarted event and receive the right data', function () {
    Event::fake([BeforeUpload::class]);

    create_request_with_files();
    $post = create_post();

    Event::assertDispatched(BeforeUpload::class, function (BeforeUpload $event) use ($post) {
        return $event->uploadable->is($post) &&
            count($event->files) === 1;
    });
});

it('listener should be able to listen to UploadStarted event', function () {
    Event::fake([BeforeUpload::class]);
    Event::listen(BeforeUpload::class, ListenersBeforeUpload::class);

    create_request_with_files();
    create_post();

    Event::assertDispatched(BeforeUpload::class);
    Event::assertListening(BeforeUpload::class, ListenersBeforeUpload::class);
});

it('should dispatch the StartUpload event and receive the right data', function () {
    Event::fake([StartUpload::class]);

    create_request_with_files();
    $post = create_post();

    Event::assertDispatched(StartUpload::class, function (StartUpload $event) use ($post) {
        $upload = $post->uploads()->first();

        return $event->uploadable->is($post) &&
            $event->filename === $upload->name;
    });
});

it('listener should be able to listen to StartUpload event', function () {
    Event::fake([StartUpload::class]);
    Event::listen(StartUpload::class, ListenersStartUpload::class);

    create_request_with_files();
    create_post();

    Event::assertDispatched(StartUpload::class);
    Event::assertListening(StartUpload::class, ListenersStartUpload::class);
});

it('should dispatch the AfterUpload event and receive the right data', function () {
    Event::fake([AfterUpload::class]);

    create_request_with_files();
    $post = create_post();

    Event::assertDispatched(AfterUpload::class, function (AfterUpload $event) use ($post) {
        $upload = $post->uploads()->first();

        return $event->uploadable->is($post) &&
            $event->upload->is($upload);
    });
});

it('listener should be able to listen to AfterUpload event', function () {
    Event::fake([AfterUpload::class]);
    Event::listen(AfterUpload::class, ListenersAfterUpload::class);

    create_request_with_files();
    create_post();

    Event::assertDispatched(AfterUpload::class);
    Event::assertListening(AfterUpload::class, ListenersAfterUpload::class);
});

it('should dispatch the CompleteUpload event and receive the right data', function () {
    Event::fake([CompleteUpload::class]);

    create_request_with_files();
    $post = create_post();

    Event::assertDispatched(CompleteUpload::class, function (CompleteUpload $event) use ($post) {
        $upload = $post->uploads()->get();

        return $event->uploadable->is($post) &&
            $event->uploads->count() === $upload->count();
    });
});

it('listener should be able to listen to CompleteUpload event', function () {
    Event::fake([CompleteUpload::class]);
    Event::listen(CompleteUpload::class, ListenersCompleteUpload::class);

    create_request_with_files();
    create_post();

    Event::assertDispatched(CompleteUpload::class);
    Event::assertListening(CompleteUpload::class, ListenersCompleteUpload::class);
});

it('should dispatch the FailedUpload event and receive the right data', function () {
    Event::fake([FailedUpload::class]);

    create_request_with_files();
    $post = new TestPost();
    $post::beforeSavingUploadUsing(fn () => throw new Exception('Failed to save post'));
    try_silently(fn () => $post = create_post($post));

    Event::assertDispatched(FailedUpload::class, function (FailedUpload $event) use ($post) {
        return $event->uploadable->is($post);
    });
});

it('listener should be able to listen to FailedUpload event', function () {
    Event::fake([FailedUpload::class]);
    Event::listen(FailedUpload::class, ListenersFailedUpload::class);

    create_request_with_files();
    $post = new TestPost();
    $post::beforeSavingUploadUsing(fn () => throw new Exception('Failed to save post'));
    try_silently(fn () => $post = create_post($post));

    Event::assertDispatched(FailedUpload::class);
    Event::assertListening(FailedUpload::class, ListenersFailedUpload::class);
});
