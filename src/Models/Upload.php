<?php

namespace NadLambino\Uploadable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use NadLambino\Uploadable\Contracts\StorageContract;

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

    protected static function booted() : void
    {
        static::forceDeleted(function (Upload $upload) {
            app(StorageContract::class)->delete($upload->path);
        });
    }

    public function uploadable(): MorphTo
    {
        return $this->morphTo();
    }
}
