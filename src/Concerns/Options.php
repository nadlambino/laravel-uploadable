<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Models\Upload;

trait Options
{
    /**
     * The callback that will be called before saving the upload.
     *
     * @var SerializableClosure|null
     */
    public static ?SerializableClosure $beforeSavingUploadCallback = null;

    /**
     * Whether or not to replace previous uploads.
     *
     * @var bool
     */
    public static bool $replacePreviousUploads = false;

    /**
     * Whether or not to upload files.
     *
     * @var bool
     */
    public static bool $dontUpload = false;

    /**
     * Whether or not to validate uploads.
     *
     * @var bool
     */
    public static bool $validateUploads = true;

    /**
     * The queue to upload on.
     *
     * @var string|null
     */
    public static string|null $uploadOnQueue = null;

    /**
     * Set the callback that will be called before saving the upload.
     * The callback will receive the upload model and the current model as arguments.
     * This callback has the higher priority than the non-static `beforeSavingUpload` method.
     *
     * @param \Closure $callback
     *
     * @return void
     */
    public static function beforeSavingUploadUsing(\Closure $callback): void
    {
        static::$beforeSavingUploadCallback = new SerializableClosure($callback);
    }

    /**
     * The callback that will be called before saving the upload.
     * The callback will receive the upload model and the current model as arguments.
     * This callback has the lower priority than the static `beforeSavingUploadUsing` method.
     *
     * @param Upload $upload
     * @param Model $model
     *
     * @return void
     */
    public function beforeSavingUpload(Upload $upload, Model $model): void { }

    /**
     * Whether or not to replace previous uploads.
     *
     * @param bool $replace
     *
     * @return void
     */
    public static function replacePreviousUploads(bool $replace = true): void
    {
        static::$replacePreviousUploads = $replace;
    }

    /**
     * Whether or not to upload files.
     *
     * @param bool $upload
     *
     * @return void
     */
    public static function dontUpload(bool $dontUpload = true): void
    {
        static::$dontUpload = $dontUpload;
    }

    /**
     * Whether or not to validate uploads.
     *
     * @param bool $validate
     *
     * @return void
     */
    public static function validateUploads(bool $validate = true): void
    {
        static::$validateUploads = $validate;
    }

    /**
     * The queue to upload on.
     *
     * @param string|null $queue
     *
     * @return void
     */
    public static function uploadOnQueue(string|null $queue = null): void
    {
        static::$uploadOnQueue = $queue;
    }
}
