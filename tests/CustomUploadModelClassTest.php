<?php

use NadLambino\Uploadable\Tests\Models\CustomUpload;
use NadLambino\Uploadable\Tests\Models\TestPost;

beforeEach(function () {
    reset_config();
});

it('can use custom model class for uploads table', function () {
    config()->set('uploadable.uploads_model', CustomUpload::class);
    create_request_with_files();
    $post = create_post(new TestPost());

    $customUpload = CustomUpload::query()
        ->where('uploadable_type', TestPost::class)
        ->where('uploadable_id', $post->id)
        ->exists();
    expect($customUpload)->toBeTrue();
    expect($post->uploads->count())->toBe(1);
});
