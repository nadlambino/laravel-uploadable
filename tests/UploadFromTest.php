<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Facades\Storage;
use NadLambino\Uploadable\Tests\Models\TestPost;

beforeEach(function () {
    reset_config();
});

it('can upload a file using the `uploadFrom` method providing an array of UploadedFile', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post = create_post($post, silently: true);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(3);

    $uploads = $post->uploads()->get();

    expect(Storage::exists($uploads[0]->path))->toBeTrue();
    expect(Storage::exists($uploads[1]->path))->toBeTrue();
    expect(Storage::exists($uploads[2]->path))->toBeTrue();
});

it('can upload a file using the `uploadFrom` method providing a string of full path of an uploaded file in the temporary disk', function () {
    $fullpath = UploadedFile::fake()->image('avatar.jpg')->store('tmp', config('uploadable.temporary_disk', 'local'));

    $post = new TestPost();
    $post->uploadFrom($fullpath);
    $post = create_post($post, silently: true);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
    expect(Storage::exists($post->uploads()->first()->path))->toBeTrue();
});
