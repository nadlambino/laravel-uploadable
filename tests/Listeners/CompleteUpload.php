<?php

namespace NadLambino\Uploadable\Tests\Listeners;

use NadLambino\Uploadable\Events\CompleteUpload as EventsCompleteUpload;

class CompleteUpload
{
    public function handle(EventsCompleteUpload $event): void {}
}
