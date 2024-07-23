<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class CustomUpload extends Model
{
    protected $table = 'uploads';

    public function uploadable()
    {
        return $this->morphTo();
    }
}
