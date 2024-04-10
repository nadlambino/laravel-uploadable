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

    public static Closure|null $afterUploadCallback = null;

    public static bool $deletePreviousUploads = false;

    public static function bootHasUpload() : void
    {
        static::observe(UploadableObserver::class);
        static::deletePreviousUploads(config('uploadable.delete_previous_uploads', false));
    }

    public function getUploads() : array
    {
        $rules = $this->getUploadRules();
        $validatable = array_filter($rules, fn ($value) => ! empty($value));

        request()->validate($validatable, $this->uploadRulesMessages());

        $fields = collect($rules)
            ->keys()
            ->zip($rules)
            ->map(fn ($pair) => is_numeric($pair[0]) ? $pair[1] : $pair[0])
            ->all();

        return request()->only($fields);
    }

    /**
     * Allows you to customize the filename of the uploaded file.
     * Make sure to return a unique filename to avoid overwriting existing files.
     *
     * @param UploadedFile $file
     * @param Model $model
     * @return string
     */
    public function getUploadFilename(UploadedFile $file, Model $model) : string
    {
        return str_replace('.', '', microtime(true)) . '-' . $file->hashName();
    }

    /**
     * Allows you to customize the path of the uploaded file.
     *
     * @param UploadedFile $file
     * @param Model $model
     * @return string
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
     * @param Upload  $upload
     * @param Model   $model
     * @param Request $request
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
     * @param Closure $callback
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
     * @param bool $remove
     *
     * @return void
     */
    public static function deletePreviousUploads(bool $remove = true) : void
    {
        static::$deletePreviousUploads = $remove;
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
        app(UploadableObserver::class)->updated($this);
    }
}
