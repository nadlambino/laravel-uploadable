<?php

namespace NadLambino\Uploadable\Contracts;

use Illuminate\Http\UploadedFile;

interface StorageContract
{
    /**
     * Change the disk to use.
     *
     * @param  string  $disk  The disk to use.
     * @return static The storage instance.
     */
    public function disk(string $disk): static;

    /**
     * Upload a file.
     *
     * @param  UploadedFile  $file  The file to upload.
     * @param  string|null  $directory  The directory where the file will be stored.
     * @param  string|null  $name  The name of the file.
     * @param  array  $options  The upload options.
     * @return string|null The path where the file was stored.
     */
    public function upload(UploadedFile $file, ?string $directory = null, ?string $name = null, array $options = []): ?string;

    /**
     * Get the file contents.
     *
     * @param  string  $file  The file to get.
     * @return string|null The file contents.
     */
    public function get(string $file): ?string;

    /**
     * Get the file URL.
     *
     * @param  string  $file  The file to get the URL.
     * @return string|null The file URL.
     */
    public function url(string $file): ?string;

    /**
     * Get the temporary URL of a file.
     *
     * @param  string  $path  The file to get the temporary URL.
     * @param  \DateTimeInterface  $expiration  The expiration date.
     * @param  array  $options  The storage options.
     * @return string|null The temporary URL.
     */
    public function temporaryUrl(string $path, ?\DateTimeInterface $expiration = null, array $options = []): ?string;

    /**
     * Delete a file.
     *
     * @param  string  $file  The file to delete.
     * @return bool If the file was deleted.
     */
    public function delete(string $file): bool;

    /**
     * Check if a file exists.
     *
     * @param  string  $file  The file to check.
     * @return bool If the file exists.
     */
    public function exists(string $file): bool;
}
