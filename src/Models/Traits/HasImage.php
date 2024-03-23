<?php

namespace NadLambino\Uploadable\Models\Traits;

trait HasImage
{
    use HasUploadable;

    public static function bootHasImage()
    {
        static::bootHasUploadable();
    }

    protected function getUploadableRequestName() : string
    {
        return 'image';
    }

    protected function getUploadableRequestRules() : array
    {
        return [
            'sometimes',
            'image'
        ];
    }
}
