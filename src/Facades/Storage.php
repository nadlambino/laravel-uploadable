<?php

namespace NadLambino\Uploadable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NadLambino\Uploadable\StorageService
 */
class Storage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NadLambino\Uploadable\StorageService::class;
    }
}
