<?php

namespace NadLambino\Uploadable\Models\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

trait HasUpload
{
    use UploadRelations, UploadValidation;

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
     * Initially set to null to allow the package to use the default value from the config file.
     *
     * @var bool|null $validateUploads
     */
    public static ?bool $validateUploads = null;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHasUpload() : void
    {
        static::observe(UploadableObserver::class);
        static::deletePreviousUploads(config('uploadable.delete_previous_uploads', false));
        static::validateUploads(static::$validateUploads ?? config('uploadable.validate', true));
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
     * Get an array of uploaded files from the request.
     *
     * @return array The uploaded files.
     */
    public function getUploads() : array
    {
        $rules = $this->getUploadRules();
        $validatable = array_filter($rules, fn($value) => ! empty($value));

        if ($this::$validateUploads) {
            request()->validate($validatable, $this->uploadRulesMessages());
        }

        $fields = collect($rules)
            ->keys()
            ->zip($rules)
            ->map(fn($pair) => is_numeric($pair[0]) ? $pair[1] : $pair[0])
            ->all();

        return request()->only($fields);
    }

    /**
     * Get the name of the uploaded file.
     * Make sure to return a unique name to avoid overwriting files.
     *
     * @param UploadedFile $file The uploaded file.
     *
     * @return string The name of the uploaded file.
     */
    public function getUploadFilename(UploadedFile $file) : string
    {
        return str_replace('.', '', microtime(true)) . '-' . $file->hashName();
    }

    /**
     * Get the path where the uploaded file will be stored.
     *
     * @param UploadedFile $file The uploaded file.
     *
     * @return string The path of the uploaded file.
     */
    public function getUploadPath(UploadedFile $file) : string
    {
        return $this->getTable() . DIRECTORY_SEPARATOR . $this->{$this->getKeyName()};
    }

    /**
     * Runs after the file has been uploaded and before the upload data are saved in the database.
     *
     * @param Upload $upload The uploaded file's model.
     * @param Model  $model  The model that owns the uploaded file.
     *
     * @return void
     */
    public function afterUpload(Upload $upload, Model $model) : void
    {

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

    /**
     * Since the upload process only happens when a model is created,
     * you can call this method to create the uploads manually.
     * Note: Only call this method when you are sure that the `created` event was not triggered.
     * Otherwise, it might cause duplicate uploads, especially when `delete_previous_uploads` is not enabled.
     *
     * @return void
     */
    public function createUploads() : void
    {
        $this::$dontUpload = false;

        app(UploadableObserver::class)->created($this);
    }

    /**
     * Since the upload process only happens when a model is updated,
     * you can call this method to update the uploads manually.
     * Note: Only call this method when you are sure that the `updated` event was not triggered.
     * Otherwise, it might cause duplicate uploads, especially when `delete_previous_uploads` is not enabled.
     *
     * @return void
     */
    public function updateUploads() : void
    {
        $this::$dontUpload = false;

        app(UploadableObserver::class)->updated($this);
    }
}
