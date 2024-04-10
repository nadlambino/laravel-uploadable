<?php

namespace NadLambino\Uploadable\Models\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

trait HasUpload
{
    use UploadRelations, UploadValidation;

    /**
     * The callback that runs after the file has been uploaded.
     *
     * @var Closure|null $afterUploadCallback
     */
    public static Closure | null $afterUploadCallback = null;

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
     * Boot the trait.
     *
     * @return void
     */
    public static function bootHasUpload() : void
    {
        static::observe(UploadableObserver::class);
        static::deletePreviousUploads(config('uploadable.delete_previous_uploads', false));
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

        request()->validate($validatable, $this->uploadRulesMessages());

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
     * @param UploadedFile $file  The uploaded file.
     * @param Model        $model The model that owns the uploaded file.
     *
     * @return string The name of the uploaded file.
     */
    public function getUploadFilename(UploadedFile $file, Model $model) : string
    {
        return str_replace('.', '', microtime(true)) . '-' . $file->hashName();
    }

    /**
     * Get the path where the uploaded file will be stored.
     *
     * @param UploadedFile $file  The uploaded file.
     * @param Model        $model The model that owns the uploaded file.
     *
     * @return string The path of the uploaded file.
     */
    public function getUploadPath(UploadedFile $file, Model $model) : string
    {
        return $model->getTable() . DIRECTORY_SEPARATOR . $model->id;
    }

    /**
     * Runs after the file has been uploaded and before the upload data are saved in the database.
     * This method is useful for modifying or storing additional details in uploads or uploadable's table.
     * Note: The request object is a new request object that doesn't have the uploaded files.
     * This is because UploadedFile objects are not serializable.
     *
     * @param Upload  $upload  The uploaded file's model.
     * @param Model   $model   The model that owns the uploaded file.
     * @param Request $request The request object.
     *
     * @return void
     */
    public function afterUpload(Upload $upload, Model $model, Request $request) : void
    {

    }

    /**
     * Allows you to run a custom callback after the file has been uploaded.
     * The callback receives the Upload model and Uploadable Model.
     * This allows you to override the `afterUpload` method in case you want to do something different.
     * Note: Make sure to call this method before saving the uploadable model.
     * Note: This won't be called when the upload is queued.
     *
     * @param Closure $callback The callback that runs after the file has been uploaded.
     *
     * @return void
     */
    public static function afterUploadUsing(Closure $callback) : void
    {
        static::$afterUploadCallback = $callback;
    }

    /**
     * Allows you to delete all the previous uploads before saving the new uploads.
     * Note: This won't be called when the upload is queued.
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
     * Note: This won't be called when the upload is queued.
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
