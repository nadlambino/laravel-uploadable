<?php

namespace NadLambino\Uploadable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NadLambino\Uploadable\Dto\UploadOptions;

/**
 * The event that is fired before the upload process starts.
 */
class BeforeUpload
{
    use Dispatchable, SerializesModels;

    public function __construct(public Model $uploadable, public array $files, public UploadOptions $options)
    {
    }
}
