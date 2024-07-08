<?php

namespace NadLambino\Uploadable\Tests\Listeners;

use NadLambino\Uploadable\Events\AfterUpload as EventsAfterUpload;

class AfterUpload
{
    public function handle(EventsAfterUpload $event): void {}
}
