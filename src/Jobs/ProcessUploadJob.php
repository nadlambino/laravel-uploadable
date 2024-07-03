<?php

namespace NadLambino\Uploadable\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Dto\UploadOptions;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array|UploadedFile|string $files,
        public readonly Model $model,
        public readonly ?UploadOptions $options = null,
    ) {}

    public function handle(): void
    {
        /** @var Upload $action */
        $action = app(Upload::class);

        $action->handle($this->files, $this->model, $this->options);
    }
}
