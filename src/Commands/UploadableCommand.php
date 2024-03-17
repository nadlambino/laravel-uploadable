<?php

namespace NadLambino\Uploadable\Commands;

use Illuminate\Console\Command;

class UploadableCommand extends Command
{
    public $signature = 'uploadable';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
