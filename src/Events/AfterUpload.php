<?php

namespace NadLambino\Uploadable\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * This event is fired after a file has been uploaded.
 * It is not yet the final step in the upload process
 * as there might still be more uploads to be done.
 * This event may fired up multiple times depending
 * on how many files are uploaded.
 */
class AfterUpload implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Model $uploadable, public Model $upload) {}
}
