<?php

namespace NadLambino\Uploadable;

use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Contracts\Uploadable as UploadableContract;

readonly class Uploadable implements UploadableContract
{
    public function __construct(private Filesystem $storage)
    {
    }

    /**
     * Upload a file.
     *
     * @param UploadedFile $file The file to upload.
     * @param string|null  $path The path where the file will be stored.
     * @param string|null  $name The name of the file.
     *
     * @return string|null The path where the file was stored.
     */
    public function upload(UploadedFile $file, ?string $path = null, ?string $name = null) : ?string
    {
        $name = $name ?? $file->hashName();

        return $this->storage->putFileAs($path, $file, $name);
    }

    /**
     * Get the file contents.
     *
     * @param string $file The file to get.
     *
     * @return string|null The file contents.
     */
    public function get(string $file) : ?string
    {
        return $this->storage->get($file);
    }

    /**
     * Get the file URL.
     *
     * @param string $file The file to get the URL.
     *
     * @return string|null The file URL.
     */
    public function url(string $file) : ?string
    {
        $url = $this->storage->url(trim($file, DIRECTORY_SEPARATOR));
        $disk = config('filesystem.default', 'local');
        $host = config("filesystem.disks.$disk.url");

        if (! isset($host)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return $host . $path;
    }

    /**
     * Get the file temporary URL.
     * If the temporary URL is not supported, the file URL will be returned.
     *
     * @param string $file       The file to get the temporary URL.
     * @param int    $expiration The expiration time in minutes.
     * @param array  $options    The options.
     *
     * @return string|null The file temporary URL.
     */
    public function temporaryUrl(string $file, int $expiration = 60, array $options = []) : ?string
    {
        try {
            return $this->storage->temporaryUrl($file, now()->addMinutes($expiration), $options);
        } catch (Exception) {
            return $this->url($file);
        }
    }

    /**
     * Delete a file.
     *
     * @param string $file The file to delete.
     *
     * @return bool If the file was deleted.
     */
    public function delete(string $file) : bool
    {
        return $this->storage->delete($file);
    }

    /**
     * Check if a file exists.
     *
     * @param string $file The file to check.
     *
     * @return bool If the file exists.
     */
    public function exists(string $file) : bool
    {
        return $this->storage->exists($file);
    }
}
