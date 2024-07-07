<?php

namespace NadLambino\Uploadable\Tests\Listeners;

use NadLambino\Uploadable\Events\FailedUpload as EventsFailedUpload;

class FailedUpload
{
    public function handle(EventsFailedUpload $event): void
    {
    }
}
