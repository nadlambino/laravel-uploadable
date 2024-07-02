<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload;
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

it('can upload a file for a given model with custom filename', function() {
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

it('can upload a file for a given model with custom path', function() {
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
