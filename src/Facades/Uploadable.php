<?php

namespace NadLambino\Uploadable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NadLambino\Uploadable\Uploadable
 */
class Uploadable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \NadLambino\Uploadable\Uploadable::class;
    }
}
