<?php

namespace NadLambino\Uploadable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Contracts\StorageContract;

class StorageService implements StorageContract
{
    public function __construct(protected Filesystem $filesystem) { }

    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $file, ?string $directory = null, ?string $filename = null): ?string
    {
        $filename ??= $file->hashName();

        return $this->filesystem->putFileAs($directory, $file, $filename);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    /**
     * @inheritDoc
     */
    public function get(string $path): ?string
    {
        return $this->filesystem->get($path);
    }

    /**
     * @inheritDoc
     */
    public function url(string $path): ?string
    {
        // TODO: Implement url() method.
        return '';
    }

    /**
     * @inheritDoc
     */
    public function temporaryUrl(string $path, int $expiration = 60, array $options = []): ?string
    {
        // TODO: Implement temporaryUrl() method.
        return '';
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): bool
    {
        // TODO: Implement delete() method.
        return false;
    }
}
