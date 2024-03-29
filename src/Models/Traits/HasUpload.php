<?php

namespace NadLambino\Uploadable\Models\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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

    public static function bootHasUpload()
    {
        static::observe(UploadableObserver::class);
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
     * @return void
     */
    public function image()
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->whereIn('extension', $this->getImageMimes());
    }

    /**
     * Returns the upload relation of image type.
     *
     * @return void
     */
    public function images()
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->whereIn('extension', $this->getImageMimes());
    }

    /**
     * Returns the upload relation of video type.
     *
     * @return MorphOne
     */
    public function video() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->whereIn('extension', $this->getVideoMimes());
    }

    /**
     * Returns the upload relation of video type.
     *
     * @return MorphMany
     */
    public function videos() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->whereIn('extension', $this->getVideoMimes());
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphOne
     */
    public function file() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable')
            ->whereNotIn('extension', array_merge($this->getImageMimes(), $this->getVideoMimes()));
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphMany
     */
    public function files() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable')
            ->whereNotIn('extension', array_merge($this->getImageMimes(), $this->getVideoMimes()));
    }

    /**
     * Add or modify rules wihthout having to rewrite the entire rule.
     *
     * @return array<string, string|array>
     */
    protected function uploadRules() : array
    {
        return [];
    }

    protected function getUploadRules() : array
    {
        return [
            'file'      => ['sometimes', 'file'],
            'files.*'   => ['sometimes', 'file'],
            'image'     => ['sometimes', 'image', $imageMimesRule = $this->getImageMimesRule()],
            'images.*'  => ['sometimes', 'image', $imageMimesRule],
            'video'     => ['sometimes', $videoMimesRule = $this->getVideoMimesRule()],
            'videos.*'  => ['sometimes', $videoMimesRule],
            ...$this->uploadRules()
        ];
    }

    protected function getImageMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getImageMimes());
    }

    protected function getVideoMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getVideoMimes());
    }

    protected function getImageMimes() : array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif', 'apng', 'ico', 'bmp', 'tiff'];
    }

    protected function getVideoMimes() : array
    {
        return ['mp4', 'mkv', 'webm', 'ogv', 'avi', 'mov', 'mpg', 'mpeg', 'wmv', 'flv', '3gp', '3g2', 'm4v'];
    }

    public function getUploads() : array
    {
        $rules = $this->getUploadRules();
        $validatable = array_filter($rules, fn ($value) => ! empty($value));

        request()->validate($validatable);

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
     * Runs before the file has been uploaded and before the upload details are saved in the database.
     * This method is useful for modifying or storing additional details in uploads table.
     *
     * @param UploadedFile $file
     * @param Model $model
     * @return void
     */
    public function beforeUpload(UploadedFile $file, Model $model) : void
    {

    }

    /**
     * Runs after the file has been uploaded and before the upload details are saved in the database.
     * This method is useful for modifying or storing additional details in uploads table.
     *
     * @param Upload $upload
     * @param UploadedFile $file
     * @param Model $model
     * @param string|null $path
     * @return void
     */
    public function afterUpload(Upload $upload, UploadedFile $file, Model $model, ?string $path) : void
    {

    }

    public static function afterUploadUsing(Closure $callback)
    {
        static::$afterUploadCallback = $callback;
    }
}
