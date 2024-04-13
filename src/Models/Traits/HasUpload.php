<?php

namespace NadLambino\Uploadable\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

trait HasUpload
{
    use UploadRelations, UploadValidation, UploadOptions;

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
        static::uploadOnQueue(static::$uploadOnQueue ?? config('uploadable.upload_on_queue_using'));
    }

    /**
     * Get an array of uploaded files from the request.
     *
     * @return array The uploaded files.
     */
    public function getUploads() : array
    {
        $rules = $this->getUploadRules();

        if ($this::$validateUploads) {
            $validatable = array_filter($rules, fn($value) => ! empty($value));
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
