<?php

namespace NadLambino\Uploadable\Models\Traits;

use Closure;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;

trait UploadOptions
{
    /**
     * The callback that runs after the file has been uploaded.
     *
     * @var SerializableClosure|null $afterUploadCallback
     */
    public static SerializableClosure | null $afterUploadCallback = null;

    /**
     * Whether to delete all the previous uploads before saving the new uploads.
     *
     * @var bool $deletePreviousUploads
     */
    public static bool $deletePreviousUploads = false;

    /**
     * Whether to upload the files or not.
     *
     * @var bool $dontUpload
     */
    public static bool $dontUpload = false;

    /**
     * Whether to validate the uploads or not.
     * The reason why it's initialized to null is that this variable is only used in this trait.
     * For example, you set the validate to true in your config, then you do `Post::validateUploads(false)`
     * in your controller then call the `Post::create($request->validated())` method.
     * What will happen is that the call for `validateUploads` won't take effect because the `create` method will boot up the trait
     * and the `validateUploads` method will be called in the `bootHasUpload` method which will override the value you passed in the `validateUploads` method.
     * So, to amend this, I made the `validateUploads` variable nullable so when it's already set, then we can use that value when the trait is booted.
     *
     * @var bool|null $validateUploads
     */
    public static ?bool $validateUploads = null;

    /**
     * The queue to use when uploading files.
     * If null, the upload will not be queued.
     *
     * @var string|null $uploadOnQueue
     */
    public static ?string $uploadOnQueue = null;

    /**
     * Configure to upload the files on a queue.
     *
     * @param string|null $queue The queue to use when uploading files.
     *
     * @return void
     */
    public static function uploadOnQueue(string|null $queue) : void
    {
        static::$uploadOnQueue = $queue;
    }

    /**
     * Configure to validate the uploads or not.
     *
     * @param bool $validate Whether to validate the uploads or not.
     *
     * @return void
     */
    public static function validateUploads(bool $validate = true) : void
    {
        static::$validateUploads = $validate;
    }

    /**
     * Runs after the file has been uploaded and before the upload data are saved in the database.
     * The callback receives the Upload model as the first argument and the Uploadable model as the second argument.
     * Note: Make sure to call this method before saving the uploadable model.
     *
     * @param Closure $callback The callback that runs after the file has been uploaded.
     *
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    public static function afterUploadUsing(Closure $callback) : void
    {
        static::$afterUploadCallback = new SerializableClosure($callback);
    }

    /**
     * Allows you to delete all the previous uploads before saving the new uploads.
     *
     * @param bool $remove Whether to delete all the previous uploads before saving the new uploads.
     *
     * @return void
     */
    public static function deletePreviousUploads(bool $remove = true) : void
    {
        static::$deletePreviousUploads = $remove;
    }

    /**
     * Allows you to disable the upload process.
     *
     * @return void
     */
    public static function dontUpload() : void
    {
        static::$dontUpload = true;
    }
}
