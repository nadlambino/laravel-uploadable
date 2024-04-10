<?php

namespace NadLambino\Uploadable\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use NadLambino\Uploadable\Models\Upload;

/**
 * @method morphOne(string $class, string $name)
 * @method morphMany(string $class, string $name)
 */
trait UploadRelations
{
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
}
