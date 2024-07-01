<?php

use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Facades\Storage;

beforeEach(function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $this->fullpath = Storage::upload($file);
});

it('can upload a file', function () {
    expect($this->fullpath)->not->toBeNull();
});

it('can upload a file with a custom name', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file, null, 'custom.jpg');

    expect($fullpath)->not->toBeNull();
    expect($fullpath)->toContain('custom.jpg');
});

it('can check if a file exists', function () {
    expect(Storage::exists($this->fullpath))->toBeTrue();
});

it('can get the file contents', function () {
    expect(Storage::get($this->fullpath))->not->toBeNull();
});

it('can get the file URL', function() {
    expect(Storage::url($this->fullpath))->not->toBeNull();
});

it('can get the temporary URL of a file', function() {
    expect(Storage::temporaryUrl($this->fullpath))->not->toBeNull();
});

it('can delete a file', function () {
    expect(Storage::delete($this->fullpath))->toBeTrue();
    expect(Storage::exists($this->fullpath))->toBeFalse();
});
