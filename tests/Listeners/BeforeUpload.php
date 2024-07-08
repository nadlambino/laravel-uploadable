<?php

namespace NadLambino\Uploadable\Tests\Listeners;

use NadLambino\Uploadable\Events\BeforeUpload as EventsBeforeUpload;

class BeforeUpload
{
    public function handle(EventsBeforeUpload $event): void {}
}
