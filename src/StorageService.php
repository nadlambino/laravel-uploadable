<?php

namespace NadLambino\Uploadable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Local\LocalFilesystemAdapter;
use NadLambino\Uploadable\Contracts\StorageContract;

class StorageService implements StorageContract
{
    public function __construct(protected Filesystem $filesystem) {}

    /**
     * {@inheritDoc}
     */
    public function upload(UploadedFile $file, ?string $directory = null, ?string $filename = null): ?string
    {
        $filename ??= $file->hashName();

        return $this->filesystem->putFileAs($directory, $file, $filename);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path): ?string
    {
        return $this->filesystem->get($path);
    }

    /**
     * {@inheritDoc}
     */
    public function url(string $path): ?string
    {
        $url = $this->filesystem->url(trim($path, DIRECTORY_SEPARATOR));
        $disk = config('filesystem.default', 'local');
        $host = config("filesystem.disks.$disk.url");

        if (! isset($host)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return $host.$path;
    }

    /**
     * {@inheritDoc}
     */
    public function temporaryUrl(string $path, int $expiration = 60, array $options = []): ?string
    {
        $expiration = now()->addMinutes($expiration);

        if ($this->filesystem->providesTemporaryUrls()) {
            return $this->filesystem->temporaryUrl($path, $expiration, $options);
        }

        if ($this->filesystem->getAdapter() instanceof LocalFilesystemAdapter) {
            return URL::temporarySignedRoute(
                'uploadable.temporary_url', $expiration, ['path' => $path]
            );
        }

        return $this->url($path);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $path): bool
    {
        return $this->filesystem->delete($path);
    }
}
