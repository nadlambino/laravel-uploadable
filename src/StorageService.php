<?php

namespace NadLambino\Uploadable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;

class StorageService
{
    public function __construct(protected Filesystem $filesystem) { }

    /**
     * Upload a file.
     *
     * @param UploadedFile $file The file to upload.
     * @param string|null $directory The directory to upload the file to.
     * @param string|null $filename The filename to use.
     *
     * @return string|null The path to the uploaded file.
     */
    public function upload(UploadedFile $file, ?string $directory = null, ?string $filename = null): ?string
    {
        $filename ??= $file->hashName();

        return $this->filesystem->putFileAs($directory, $file, $filename);
    }

    /**
     * Check if a file exists at the given path.
     *
     * @param string $path The path to the file.
     *
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }
}
