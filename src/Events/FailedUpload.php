<?php

namespace NadLambino\Uploadable\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * This event is fired when the upload process fails.
 */
class FailedUpload
{
    use Dispatchable, SerializesModels;

    public function __construct(public Throwable $exception, public Model $uploadable)
    {
    }
}
