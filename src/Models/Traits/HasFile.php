<?php

namespace NadLambino\Uploadable\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Contracts\Uploadable;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

/**
 * @method morphOne(string $class, string $name)
 * @method morphMany(string $class, string $name)
 */
trait HasFile
{
    private ?UploadedFile $uploadedFile = null;

    public static function bootHasFile()
    {
        static::observe(UploadableObserver::class);
    }

    public function file() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    public function files() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    public function setFileUpload(UploadedFile $file) : static
    {
        $this->uploadedFile = $file;

        return $this;
    }

    public function getFileUpload() : ?UploadedFile
    {
        if (is_null($this->file)) {
            $this->uploadedFile = request()->file('file');
        }

        return $this->uploadedFile;
    }

    public function fileUrl() : ?string
    {
        if (! $this->file?->path) {
            return null;
        }

        return app(Uploadable::class)->url($this->file->path);
    }
}
