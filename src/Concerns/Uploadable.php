<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Http\UploadedFile;

trait Uploadable
{
    use Relations;

    public function getUploadFilename(UploadedFile $file) : string
    {
        return str_replace('.', '', microtime(true)) . '-' . $file->hashName();
    }

    public function getUploadPath(UploadedFile $file) : string
    {
        return $this->getTable() . DIRECTORY_SEPARATOR . $this->{$this->getKeyName()};
    }
}
