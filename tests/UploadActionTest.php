<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomFilename;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomPath;

function uploadFileFor(Model $model, array|UploadedFile $files = null, array $options = [])
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

    uploadFileFor($post);

    expect($post->uploads()->first())->not->toBeNull();
});

it('can upload a file for a given model with custom filename', function () {
    $post = new TestPostWithCustomFilename();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post, $file = UploadedFile::fake()->image('avatar.jpg'));

    expect($post->uploads()->first()->name)->toBe($file->getClientOriginalName());
});

it('can upload a file for a given model with custom path', function () {
    $post = new TestPostWithCustomPath();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post);

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

    uploadFileFor($post, options: ['before_saving_upload_using' => TestPost::$beforeSavingUploadCallback]);

    expect($post->uploads()->first()->tag)->toBe($tag);
});

it('can upload a file and save the upload with additional data using the `beforeSavingUpload` method', function () {
    $post = new TestPost();
    $post->title = fake()->sentence();
    $post->body = fake()->paragraph();
    $post->save();

    uploadFileFor($post);

    expect($post->uploads()->first()->tag)->toBe($post->title);
});
