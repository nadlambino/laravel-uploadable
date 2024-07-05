<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Tests\Models\TestPost;

beforeEach(function () {
    reset_config();
});

it('should return only one image when querying the `image` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post = create_post($post);

    expect($post->image()->get()->count())->toBe(1);
    expect($post->image->original_name)->toContain('avatar1.jpg');
});

it('should return only the images when querying the `images` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'video' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
    ]);
    $post = create_post($post);

    expect($post->images()->get()->count())->toBe(2);
    expect($post->images->count())->toBe(2);
});

it('should return only one video when querying the `video` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'videos' => [
            UploadedFile::fake()->create('video1.mp4', 1000, 'video/mp4'),
            UploadedFile::fake()->create('video2.mp4', 1000, 'video/mp4'),
        ],
    ]);
    $post = create_post($post);

    expect($post->video()->get()->count())->toBe(1);
    expect($post->video->original_name)->toContain('video1.mp4');
});

it('should return only the videos when querying the `videos` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'videos' => [
            UploadedFile::fake()->create('video1.mp4', 1000, 'video/mp4'),
            UploadedFile::fake()->create('video2.mp4', 1000, 'video/mp4'),
        ],
    ]);
    $post = create_post($post);

    expect($post->videos()->get()->count())->toBe(2);
    expect($post->videos->count())->toBe(2);
});

it('should return only one document when querying the `document` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'document' => [
            UploadedFile::fake()->create('document1.pdf', 1000, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1000, 'application/pdf'),
        ],
    ]);
    $post = create_post($post);

    expect($post->document()->get()->count())->toBe(1);
    expect($post->document->original_name)->toContain('document1.pdf');
});

it('should return only the documents when querying the `documents` relation method', function () {
    $post = new TestPost();
    $post->uploadFrom([
        'images' => [
            UploadedFile::fake()->image('avatar1.jpg'),
            UploadedFile::fake()->image('avatar2.jpg'),
        ],
        'document' => [
            UploadedFile::fake()->create('document1.pdf', 1000, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1000, 'application/pdf'),
        ],
    ]);
    $post = create_post($post);

    expect($post->documents()->get()->count())->toBe(2);
    expect($post->documents->count())->toBe(2);
});
