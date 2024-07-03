<?php

namespace NadLambino\Uploadable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Concerns\Uploadable;

class TestPostWithCustomRules extends Model
{
    use Uploadable;

    protected $table = 'test_posts';

    protected function uploadRules(): array
    {
        return [
            'image' => 'mimes:jpeg,jpg,png',
        ];
    }

    protected function uploadRuleMessages(): array
    {
        return [
            'image.mimes' => 'Only jpeg, jpg and png files are allowed',
        ];
    }
}
