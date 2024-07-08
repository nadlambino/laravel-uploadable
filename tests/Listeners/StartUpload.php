<?php

namespace NadLambino\Uploadable\Tests\Listeners;

use NadLambino\Uploadable\Events\StartUpload as EventsStartUpload;

class StartUpload
{
    public function handle(EventsStartUpload $event): void {}
}
