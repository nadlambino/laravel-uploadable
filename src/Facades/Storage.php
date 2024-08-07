<?php

namespace NadLambino\Uploadable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static ?string upload(\Illuminate\Http\UploadedFile $file, ?string $directory = null, ?string $name = null, array $options = [])
 * @method static bool exists(string $path)
 * @method static ?string get(string $path)
 * @method static ?string url(string $path)
 * @method static ?string temporaryUrl(string $path, \DateTimeInterface $expiration = null, array $options = [])
 * @method static bool delete(string $path)
 *
 * @see \NadLambino\Uploadable\StorageService
 */
class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NadLambino\Uploadable\StorageService::class;
    }
}
