<?php

namespace NadLambino\Uploadable;

use DateTime;
use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use League\Flysystem\Local\LocalFilesystemAdapter;
use NadLambino\Uploadable\Contracts\StorageContract;

class StorageService implements StorageContract
{
    public function __construct(protected FilesystemAdapter $filesystem) {}

    /**
     * {@inheritDoc}
     */
    public function upload(UploadedFile $file, ?string $directory = null, ?string $filename = null, array $options = []): ?string
    {
        $filename ??= $file->hashName();

        return $this->filesystem->putFileAs($directory, $file, $filename, $options);
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
    public function temporaryUrl(string $path, ?\DateTimeInterface $expiration = null, array $options = []): ?string
    {
        $expirationFromConfig = config('uploadable.temporary_url.expiration', '1 hour');
        $applicationTimezone = config('app.timezone', 'UTC');
        $timezone = new \DateTimeZone($applicationTimezone);

        $expiresAt = match (true) {
            $expiration instanceof DateTimeInterface => $expiration,
            $expiration === null && $expirationFromConfig instanceof DateTimeInterface => $expirationFromConfig,
            $expiration === null && is_string($expirationFromConfig) => new DateTime($expirationFromConfig, $timezone),
            default => new DateTime('now', $timezone),
        };

        if ($this->filesystem->providesTemporaryUrls()) {
            return $this->filesystem->temporaryUrl($path, $expiresAt, $options);
        }

        if ($this->filesystem->getAdapter() instanceof LocalFilesystemAdapter) {
            return URL::temporarySignedRoute(
                'uploadable.temporary_url', $expiresAt, ['path' => $path]
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
