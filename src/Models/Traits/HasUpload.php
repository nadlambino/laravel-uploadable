<?php

namespace NadLambino\Uploadable\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Observers\UploadableObserver;

/**
 * @method morphOne(string $class, string $name)
 * @method morphMany(string $class, string $name)
 */
trait HasUpload
{
    public static function bootHasUpload()
    {
        static::observe(UploadableObserver::class);
    }

    public function upload() : MorphOne
    {
        return $this->morphOne(Upload::class, 'uploadable');
    }

    public function uploads() : MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }

    protected function getUploadRules() : array
    {
        return [
            'file' => ['sometimes', 'file'],
            'image' => ['sometimes', 'image'],
        ];
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
}
