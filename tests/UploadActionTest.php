<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;

it('can upload a file for a given model', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($file, $post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $post = new TestPostWithCustomFilename();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($file, $post);

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $post = new TestPostWithCustomPath();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($file, $post);

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

    $file = UploadedFile::fake()->image('avatar.jpg');
    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($file, $post, ['before_saving_upload_using' => TestPost::$beforeSavingUploadCallback]);

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    $file = UploadedFile::fake()->image('avatar.jpg');
    /** @var Upload $action */
    $action = app(Upload::class);
    $action->handle($file, $post);

    expect($post->uploads()->first()->tag)->toBe($post->title);
});
