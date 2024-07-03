<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPostWithSoftDeletes extends Model
{
    use Uploadable, SoftDeletes;

    protected $table = 'test_posts';
}
