<?php

namespace NadLambino\Uploadable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * This event is fired when it's about to start the upload process on a single file.
 * This event may fired up multiple times depending on the number of files to be uploaded.
 */
class StartUpload
{
    use Dispatchable, SerializesModels;

    public function __construct(public Model $uploadable, public string $filename, public string $path)
    {
    }
}
