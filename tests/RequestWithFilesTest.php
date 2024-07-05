<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Tests\Models\TestPost;
use NadLambino\Uploadable\Tests\Models\TestPostWithCustomRules;

beforeEach(function () {
    reset_config();
});

it('can upload a single file from the request', function () {
    create_request_with_files();

    $post = create_post(silently: true);
    $files = $post->getUploads();
    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

it('can upload multiple files from the request', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
        UploadedFile::fake()->image('avatar2.jpg'),
    ]);

    $post = create_post(silently: true);
    $files = $post->getUploads();
    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(2);
});

it('can upload an image from request from custom request field', function () {
    create_request_with_files(type: 'avatar');
    $post = create_post(new TestPostWithCustomRules());

    expect($post->uploads()->count())->toBe(1);
});

it('should validate a single invalid image', function () {
    create_request_with_files([
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid images', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ]);

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a singe invalid video', function () {
    create_request_with_files(type: 'video');

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid videos', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ], type: 'video');

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate a single invalid document', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
    ], type: 'document');

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should validate multiple invalid documents', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.jpg'),
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ], type: 'document');

    $post = create_post(silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $post->getUploads();
});

it('should override the default validation rules and messages', function () {
    create_request_with_files([
        UploadedFile::fake()->image('avatar1.webp'),
    ]);

    $post = create_post(new TestPostWithCustomRules(), silently: true);

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $this->expectExceptionMessage('Only jpeg, jpg and png files are allowed');

    $post->getUploads();
});

it('should skip the validation for a specific uploadable model, set from config', function () {
    config()->set('uploadable.validate', false);

    create_request_with_files([
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ], type: 'image');

    $post = create_post(silently: true);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});

it('should skip the validation for a specific uploadable model, set from class', function () {
    // Emulate that it is set to true by default
    config()->set('uploadable.validate', true);

    create_request_with_files([
        UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf'),
    ], type: 'image');

    TestPost::validateUploads(false);
    $post = create_post(silently: true);

    $files = $post->getUploads();

    upload_file_for($post, $files);

    expect($post->uploads()->count())->toBe(1);
});
