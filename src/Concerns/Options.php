<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Models\Upload;

trait Options
{
    public static SerializableClosure|null $beforeSavingUploadCallback = null;

    public static function beforeSavingUploadUsing(\Closure $callback): void
    {
        static::$beforeSavingUploadCallback = new SerializableClosure($callback);
    }

    public function beforeSavingUpload(Upload $upload, Model $model): void
    {

    }
}
