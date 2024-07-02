<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPostWithCustomFilename extends Model
{
    use Uploadable;

    protected $table = 'test_posts';

    public function getUploadFilename(UploadedFile $file): string
    {
        return $file->getClientOriginalName();
    }
}
