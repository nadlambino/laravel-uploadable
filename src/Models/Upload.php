<?php

namespace NadLambino\Uploadable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use NadLambino\Uploadable\Contracts\Uploadable;

class Upload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path',
        'name',
        'original_name',
        'extension',
        'size',
        'type',
    ];

    public function uploadable() : MorphTo
    {
        return $this->morphTo();
    }

    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return app(Uploadable::class)->url($this->path);
    }
}
