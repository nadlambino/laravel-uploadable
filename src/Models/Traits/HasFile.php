<?php

namespace NadLambino\Uploadable\Models\Traits;

trait HasFile
{
    use HasUploadable;

    public static function bootHasFile()
    {
        static::bootHasUploadable();
    }

    protected function getUploadableRequestName() : string
    {
        return 'file';
    }
}
