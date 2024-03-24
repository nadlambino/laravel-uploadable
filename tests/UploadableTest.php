<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use NadLambino\Uploadable\Facades\Uploadable;

it('can upload a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Uploadable::upload($file, 'users', 'avatar');

    expect($fullpath)->not->toBeNull();
    expect(Uploadable::exists($fullpath))->toBeTrue();
});

it('can delete a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Uploadable::upload($file, 'users', 'avatar');

    expect(Uploadable::exists($fullpath))->toBeTrue();

    Uploadable::delete($fullpath);

    expect(Uploadable::exists($fullpath))->toBeFalse();
});

it('can get a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Uploadable::upload($file, 'users', 'avatar');

    expect(Uploadable::exists($fullpath))->toBeTrue();
    expect(Uploadable::get($fullpath))->not->toBeNull();
});

it('can get a file url', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Uploadable::upload($file, 'users', 'avatar');

    expect(Uploadable::exists($fullpath))->toBeTrue();
    expect(Uploadable::url($fullpath))->not->toBeNull();
});
