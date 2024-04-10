<?php

namespace NadLambino\Uploadable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NadLambino\Uploadable\Actions\UploadAction;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly array $files,
        private readonly Model $model,
        private readonly array $options = [],
    )
    {
    }

    public function handle() : void
    {
        /** @var UploadAction $action */
        $action = app()->make(UploadAction::class);

        $action->handle($this->files, $this->model, $this->options);
    }
}
