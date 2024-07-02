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
        return $this->morphOne(Upload::class, 'uploadable');
    }

    /**
     * Returns the upload relation of all types.
     *
     * @return MorphMany The upload relation.
     */
    public function uploads(): MorphMany
    {
        return $this->morphMany(Upload::class, 'uploadable');
    }
}
