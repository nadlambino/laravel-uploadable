<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Concerns\Uploadable;
use NadLambino\Uploadable\Models\Upload;

class TestPost extends Model
{
    use Uploadable;

    public function beforeSavingUpload(Upload $upload, Model $model): void
    {
        $upload->tag = $model->title;
    }
}
