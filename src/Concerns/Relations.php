<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use NadLambino\Uploadable\Models\Upload;

trait Relations
{
    /**
     * Returns the upload relation of all types.
     *
     * @return MorphOne The upload relation.
     */
    public function upload(): MorphOne
    {
        return $this->morphOne(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->limit(1);
    }

    /**
     * Returns the upload relation of all types.
     *
     * @return MorphMany The upload relation.
     */
    public function uploads(): MorphMany
    {
        return $this->morphMany(config('uploadable.uploads_model', Upload::class), 'uploadable');
    }

    /**
     * Returns the upload relation of image type.
     *
     * @return MorphOne The upload relation.
     */
    public function image(): MorphOne
    {
        return $this->morphOne(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getImageMimes())
                    ->orWhereIn('type', $mimes);
            })
            ->limit(1);
    }

    /**
     * Returns the upload relation of image type.
     *
     * @return MorphMany The upload relation.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getImageMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of video type.
     *
     * @return MorphOne The upload relation.
     */
    public function video(): MorphOne
    {
        return $this->morphOne(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getVideoMimes())
                    ->orWhereIn('type', $mimes);
            })
            ->limit(1);
    }

    /**
     * Returns the upload relation of video type.
     *
     * @return MorphMany The upload relation.
     */
    public function videos(): MorphMany
    {
        return $this->morphMany(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getVideoMimes())
                    ->orWhereIn('type', $mimes);
            });
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphOne The upload relation.
     */
    public function document(): MorphOne
    {
        return $this->morphOne(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getDocumentMimes())
                    ->orWhereIn('type', $mimes);
            })
            ->limit(1);
    }

    /**
     * Returns the upload relation of type that is not image or video.
     *
     * @return MorphMany The upload relation.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(config('uploadable.uploads_model', Upload::class), 'uploadable')
            ->where(function ($query) {
                $query->whereIn('extension', $mimes = $this->getDocumentMimes())
                    ->orWhereIn('type', $mimes);
            });
    }
}
