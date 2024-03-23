<?php

namespace NadLambino\Uploadable\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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

    protected function getUploadableRequestName() : string
    {
        return Str::of(__TRAIT__)->classBasename()->replace('Has', '')->lower();
    }

    protected function getUploadableRequestRules() : array
    {
        return [
            'sometimes',
            'file'
        ];
    }

    public function setUploadable(UploadedFile $file) : static
    {
        request()->validate([
            $this->getUploadableRequestName() => $this->getUploadableRequestRules(),
        ]);

        $this->uploadedFile = $file;

        return $this;
    }

    public function getUploadable() : ?UploadedFile
    {
        $this->setUploadable(request()->file($this->getUploadableRequestName()));

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
