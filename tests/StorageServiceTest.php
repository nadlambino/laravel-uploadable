<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Facades\Storage;

it('can upload a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect($fullpath)->not->toBeNull();
});

it('can upload a file with a custom name', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, null, 'custom.jpg');

    expect($fullpath)->not->toBeNull();
    expect($fullpath)->toContain('custom.jpg');
});

it('can upload a file with a custom path', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, 'custom');

    expect($fullpath)->not->toBeNull();
    expect($fullpath)->toContain('custom');
});

it('can upload a file with a custom name and path', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, 'custom', 'custom.jpg');

    expect($fullpath)->not->toBeNull();
    expect($fullpath)->toContain('custom');
    expect($fullpath)->toContain('custom.jpg');
});

it('can upload a file with options', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, options: ['visibility' => 'public']);

    // Unfortunately, there is no way to assert the visibility of the file
    // and other options as well, so we just check if the file was uploaded.
    expect($fullpath)->not->toBeNull();
});

it('can check if a file exists', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::exists($fullpath))->toBeTrue();
});

it('can get the file contents', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::get($fullpath))->not->toBeNull();
});

it('can get the file URL', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::url($fullpath))->not->toBeNull();
});

it('can get the temporary URL of a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::temporaryUrl($fullpath))->not->toBeNull();
});

it('can delete a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::delete($fullpath))->toBeTrue();
    expect(Storage::exists($fullpath))->toBeFalse();
});
