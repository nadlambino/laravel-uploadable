<?php

use Illuminate\Support\Facades\Route;
use NadLambino\Uploadable\Facades\Storage;

Route::get(trim(config('uploadable.temporary_url.path', '/temporary'), '/').'/{path}', fn ($path) => Storage::get($path))
    ->where('path', '.*')
    ->name('uploadable.temporary_url')
    ->middleware(config('uploadable.temporary_url.middleware', []));
