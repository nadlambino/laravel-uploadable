<?php

namespace NadLambino\Uploadable\Events;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * This event is fired after upload process is completed
 * including all the necessary clean ups.
 */
class CompleteUpload
{
    use Dispatchable, SerializesModels;

    public function __construct(public Model $uploadable, public Collection $uploads)
    {
    }
}
