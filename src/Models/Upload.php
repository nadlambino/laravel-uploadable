<?php

namespace NadLambino\Uploadable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Upload extends Model
{
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
}
