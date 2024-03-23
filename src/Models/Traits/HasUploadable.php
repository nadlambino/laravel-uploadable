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
trait HasUploadable
{
    private ?UploadedFile $uploadedFile = null;

    public static function bootHasUploadable()
    {
        static::observe(UploadableObserver::class);
    }

    public function uploadable() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    public function uploadables() : MorphMany
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

    public function uploadableUrl() : ?string
    {
        if (! $this->uploadable?->path) {
            return null;
        }

        return app(Uploadable::class)->url($this->uploadable->path);
    }
}
