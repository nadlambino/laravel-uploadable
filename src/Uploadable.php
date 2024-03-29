<?php

namespace NadLambino\Uploadable;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Contracts\Uploadable as UploadableContract;

class Uploadable implements UploadableContract
{
    private string $basePath;

    private string $environment;

    public function __construct(private readonly Filesystem $storage)
    {
        $this->environment = config('app.env');
        $this->basePath = trim(config("uploadable.disks.$this->environment.directory"), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function upload(UploadedFile $file, ?string $path = null, ?string $name = null) : ?string
    {
        $path = $path ? $this->basePath . trim($path, DIRECTORY_SEPARATOR) : $this->basePath;
        $name = $name ?? $file->hashName();

        return $this->storage->putFileAs($path, $file, $name);
    }

    public function get(string $file) : ?string
    {
        return $this->storage->get($file);
    }

    public function url(string $file) : ?string
    {
        $url = $this->storage->url(trim($file, DIRECTORY_SEPARATOR));
        $host = config("uploadable.disks.$this->environment.host");

        if (! isset($host)) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return $host . $path;
    }

    public function temporaryUrl(string $file, int $expiration = 60, array $options = []) : ?string
    {
        try {
            return $this->storage->temporaryUrl($file, now()->addMinutes($expiration), $options);
        } catch (\Exception) {
            return $this->url($file);
        }
    }

    public function delete(string $file) : bool
    {
        return $this->storage->delete($file);
    }

    public function exists(string $file) : bool
    {
        return $this->storage->exists($file);
    }
}
