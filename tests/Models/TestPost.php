<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPost extends Model
{
    use Uploadable;

    protected $guarded = [];

    public function beforeSavingUpload(Model $upload, Model $model): void
    {
        $upload->tag = $model->title;
    }
}
