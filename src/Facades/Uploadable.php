<?php

namespace NadLambino\Uploadable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NadLambino\Uploadable\Uploadable
 *
 * @method static string|null upload(\Illuminate\Http\UploadedFile $file, ?string $path = null, ?string $name = null) Uploads a file and optionally stores it at a given path with a given name.
 * @method static string|null get(string $file) Retrieves the contents of a file.
 * @method static string|null url(string $file) Retrieves the URL of a file.
 * @method static string|null temporaryUrl(string $file, int $expiration = 60, array $options = []) Retrieves a temporary URL for a file, which expires after a given number of minutes.
 * @method static bool delete(string $file) Deletes a file.
 * @method static bool exists(string $file) Checks if a file exists.
 */
class Uploadable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \NadLambino\Uploadable\Uploadable::class;
    }
}
