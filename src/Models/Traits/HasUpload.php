<?php

namespace NadLambino\Uploadable\Models\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

/**
 * @method morphOne(string $class, string $name)
 * @method morphMany(string $class, string $name)
 */
trait HasUpload
{
    public static Closure|null $afterUploadCallback = null;

    public static bool $deletePreviousUploads = false;

    public static function bootHasUpload() : void
    {
        static::observe(UploadableObserver::class);
        static::deletePreviousUploads(config('uploadable.delete_previous_uploads', false));
    }

    /**
     * Returns the upload relation of all types.
     *
     * @return MorphOne
     */
    public function upload() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    /**
     * Returns the upload relation of all types.
     *
     * @return MorphMany
     */
    public function uploads() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    /**
     * Returns the upload relation of image type.
     *
     * @return MorphOne
     */
    public function image(): MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getImageMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of image type.
     *
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getImageMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of video type.
     *
     */
    public function video() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getVideoMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of video type.
     *
     * @return MorphMany
     */
    public function videos() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getVideoMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphOne
     */
    public function document() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getDocumentMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphMany
     */
    public function documents() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getDocumentMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Add or modify rules without having to rewrite the entire rule.
     *
     * @return array<string, string|array>
     */
    protected function uploadRules() : array
    {
        return [];
    }

    /**
     * Add or modify rules' messages.
     *
     * @return array<string, string>
     */
    protected function uploadRulesMessages() : array
    {
        return [];
    }

    protected function getUploadRules() : array
    {
        return [
            'document'      => ['sometimes', 'file', $documentMimesRule = $this->getDocumentMimesRule()],
            'documents.*'   => ['sometimes', 'file', $documentMimesRule],
            'image'         => ['sometimes', 'image', $imageMimesRule = $this->getImageMimesRule()],
            'images.*'      => ['sometimes', 'image', $imageMimesRule],
            'video'         => ['sometimes', $videoMimesRule = $this->getVideoMimesRule()],
            'videos.*'      => ['sometimes', $videoMimesRule],
            ...$this->uploadRules()
        ];
    }

    protected function getImageMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getImageMimes());
    }

    protected function getImageMimes() : array
    {
        return config('uploadable.mimes.image');
    }

    protected function getVideoMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getVideoMimes());
    }

    protected function getVideoMimes() : array
    {
        return config('uploadable.mimes.video');
    }
    protected function getDocumentMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getDocumentMimes());
    }

    protected function getDocumentMimes() : array
    {
        return config('uploadable.mimes.document');
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
        return $file->hashName();
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
     * Since the upload process only happens when a model is created or updated,
     * you can call this method to process the uploads manually.
     * This is when the way you create or update the model doesn't trigger the `created` nor `updated` event.
     * Note: Only call this method when you are sure that the `created` or `updated` event is not triggered. Otherwise, it will cause duplicate uploads.
     *
     * @return void
     */
    public function createUploads() : void
    {
        app(UploadableObserver::class)->created($this);
    }

    /**
     * Since the upload process only happens when a model is created or updated,
     * you can call this method to process the uploads manually.
     * This is when you want to add new uploads into a model, but you didn't actually update the model.
     * Note: Only call this method when you are sure that the `created` or `updated` event is not triggered. Otherwise, it will cause duplicate uploads.
     *
     * @return void
     */
    public function updateUploads() : void
    {
        app(UploadableObserver::class)->updated($this);
    }
}
