<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Models\Upload;

trait Options
{
    /**
     * The callback that will be called before saving the upload.
     */
    public static ?SerializableClosure $beforeSavingUploadCallback = null;

    /**
     * Whether or not to replace previous uploads.
     * Initally set to null so that it won't override the config.
     */
    public static ?bool $replacePreviousUploads = null;

    /**
     * Whether or not to upload files.
     */
    public static bool $disableUpload = false;

    /**
     * Whether or not to validate uploads.
     * Initally set to null so that it won't override the config.
     */
    public static ?bool $validateUploads = null;

    /**
     * The queue to upload on.
     * Initally set to null so that it won't override the config.
     */
    public static ?string $uploadOnQueue = null;

    /**
     * The options for uploading the file in the storage.
     * This is useful when you want to set the visibility of the file, etc.
     */
    public static ?array $uploadStorageOptions = null;

    /**
     * The disk to upload the file.
     */
    public static ?string $uploadDisk = null;

    /**
     * Set the callback that will be called before saving the upload.
     * The callback will receive the upload model and the current model as arguments.
     * This callback has the higher priority than the non-static `beforeSavingUpload` method.
     */
    public static function beforeSavingUploadUsing(\Closure $callback): void
    {
        static::$beforeSavingUploadCallback = new SerializableClosure($callback);
    }

    /**
     * The callback that will be called before saving the upload.
     * The callback will receive the upload model and the current model as arguments.
     * This callback has the lower priority than the static `beforeSavingUploadUsing` method.
     */
    public function beforeSavingUpload(Upload $upload, Model $model): void {}

    /**
     * Whether or not to replace previous uploads.
     */
    public static function replacePreviousUploads(bool $replace = true): void
    {
        static::$replacePreviousUploads = $replace;
    }

    /**
     * Whether or not to upload files.
     *
     * @param  bool  $upload
     */
    public static function disableUpload(bool $disableUpload = true): void
    {
        static::$disableUpload = $disableUpload;
    }

    /**
     * Whether or not to validate uploads.
     */
    public static function validateUploads(bool $validate = true): void
    {
        static::$validateUploads = $validate;
    }

    /**
     * The queue to upload on.
     */
    public static function uploadOnQueue(?string $queue = null): void
    {
        static::$uploadOnQueue = $queue;
    }

    /**
     * The options for uploading the file in the storage.
     */
    public static function uploadStorageOptions(?array $options = null): void
    {
        static::$uploadStorageOptions = $options;
    }

    /**
     * Set the disk to upload the file.
     */
    public static function uploadDisk(string $disk): void
    {
        static::$uploadDisk = $disk;
    }

    /**
     * The options for the upload process.
     */
    public function getUploadOptions(): UploadOptions
    {
        return new UploadOptions(
            beforeSavingUploadUsing: static::$beforeSavingUploadCallback,
            disableUpload: static::$disableUpload,
            disk: static::$uploadDisk,
            originalAttributes: $this->getOriginalOfAffectedAttributes(),
            uploadStorageOptions: $this->getStorageOptions(),
            replacePreviousUploads: static::$replacePreviousUploads,
            uploadOnQueue: static::$uploadOnQueue,
        );
    }

    private function getStorageOptions(): array
    {
        return static::$uploadStorageOptions ?? $this->getUploadStorageOptions();
    }

    private function getOriginalOfAffectedAttributes(): array
    {
        $affectedAttributes = array_keys($this->getChanges());

        if (empty($affectedAttributes)) {
            return $this->getOriginal();
        }

        return collect($affectedAttributes)
            ->map(fn ($attribute) => ['attribute' => $attribute, 'value' => $this->getOriginal($attribute)])
            ->pluck('value', 'attribute')
            ->toArray();
    }
}
