<?php

namespace NadLambino\Uploadable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Contracts\Uploadable as UploadableContract;

class Uploadable implements UploadableContract
{
    private string $basePath;

    public function __construct(private readonly Filesystem $storage)
    {
        $this->basePath = trim(app('uploadable')->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function upload(UploadedFile $file, ?string $path = null, ?string $name = null) : ?string
    {
        $path = $path ? $this->basePath . trim($path, DIRECTORY_SEPARATOR) : $this->basePath;
        $name = $name ?? $file->hashName();

        return $this->storage->putFileAs($path, $file, $name);
    }
}
