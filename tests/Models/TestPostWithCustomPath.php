<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPostWithCustomPath extends Model
{
    use Uploadable;

    protected $table = 'test_posts';

    public function getUploadPath(UploadedFile $file): string
    {
        return 'custom_path' . DIRECTORY_SEPARATOR;
    }
}
