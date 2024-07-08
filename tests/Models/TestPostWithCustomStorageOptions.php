<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPostWithCustomStorageOptions extends Model
{
    use Uploadable;

    protected $table = 'test_posts';

    public function getUploadStorageOptions(): array
    {
        return [
            'visibility' => 'public',
        ];
    }
}
