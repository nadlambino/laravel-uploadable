<?php

namespace NadLambino\Uploadable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NadLambino\Uploadable\FileService
 */
class FileService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NadLambino\Uploadable\FileService::class;
    }
}
