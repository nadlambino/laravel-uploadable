<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Facades\Storage;

it('can upload a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, 'users', 'avatar');

    expect($fullpath)->not->toBeNull();
});

it('can check if a file exists', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, 'users', 'avatar');

    expect(Storage::exists($fullpath))->toBeTrue();
});

it('can get the file contents', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, 'users', 'avatar');

    expect(Storage::get($fullpath))->not->toBeNull();
});
