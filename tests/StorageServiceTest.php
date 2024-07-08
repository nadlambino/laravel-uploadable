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
    expect(Storage::temporaryUrl($fullpath))->toContain(config('uploadable.temporary_url.path'));
    expect(Storage::temporaryUrl($fullpath))->toContain('expires');
    expect(Storage::temporaryUrl($fullpath))->toContain('signature');
});

it('can get the temporary URL of a file with correct expiration set from config as string', function () {
    config()->set('uploadable.temporary_url.expiration', '30 minutes');

    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    $temporaryUrl = Storage::temporaryUrl($fullpath);
    $queryString = parse_url($temporaryUrl, PHP_URL_QUERY);
    parse_str($queryString, $query);
    $expiration = $query['expires'];

    expect($temporaryUrl)->not->toBeNull();
    expect($expiration)->toBeGreaterThanOrEqual(now()->add(config('uploadable.temporary_url.expiration'))->timestamp);
});

it('can get the temporary URL of a file with correct expiration set from config as DateTime', function () {
    config()->set('uploadable.temporary_url.expiration', new DateTime('3 hours'));

    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    $temporaryUrl = Storage::temporaryUrl($fullpath);
    $queryString = parse_url($temporaryUrl, PHP_URL_QUERY);
    parse_str($queryString, $query);
    $expiration = $query['expires'];

    expect($temporaryUrl)->not->toBeNull();
    expect($expiration)->toBeGreaterThanOrEqual(config('uploadable.temporary_url.expiration')->getTimestamp());
});

it('can get the temporary URL of a file with correct timezone', function () {
    config()->set('uploadable.temporary_url.expiration', '1 hour');
    config()->set('app.timezone', 'Asia/Manila');

    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    $temporaryUrl = Storage::temporaryUrl($fullpath);
    $queryString = parse_url($temporaryUrl, PHP_URL_QUERY);
    parse_str($queryString, $query);
    $expiration = $query['expires'];

    expect($temporaryUrl)->not->toBeNull();
    expect($expiration)->toBeGreaterThanOrEqual(now()->setTimezone('Asia/Manila')->timestamp);
    expect($expiration)->not->toBe(now()->addHour()->timestamp);
});

it('can get the temporary URL of a file with correct expiration set from the method', function () {
    config()->set('uploadable.temporary_url.expiration', '30 minutes');
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    $additional = 60;
    $expiration = now()->addMinutes($additional);
    $temporaryUrl = Storage::temporaryUrl($fullpath, $expiration);

    $queryString = parse_url($temporaryUrl, PHP_URL_QUERY);
    parse_str($queryString, $query);
    $expiration = $query['expires'];

    expect($temporaryUrl)->not->toBeNull();
    expect($expiration)->toBeGreaterThanOrEqual(now()->addMinutes($additional)->timestamp);
});

it('can delete a file', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');
    $fullpath = Storage::upload($file);

    expect(Storage::delete($fullpath))->toBeTrue();
    expect(Storage::exists($fullpath))->toBeFalse();
});
