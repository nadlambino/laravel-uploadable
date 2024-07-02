<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Http\UploadedFile;

trait Uploadable
{
    use Options, Relations;

    public static function bootUploadable(): void
    {
        static::replacePreviousUploads(config('uploadable.replace_previous_uploads', false));
        static::validateUploads(config('uploadable.validate', true));
        static::uploadOnQueue(config('uploadable.upload_on_queue', null));
    }

    public function getUploadFilename(UploadedFile $file): string
    {
        return str_replace('.', '', microtime(true)).'-'.$file->hashName();
    }

    public function getUploadPath(UploadedFile $file): string
    {
        return $this->getTable().DIRECTORY_SEPARATOR.$this->{$this->getKeyName()};
    }
}
