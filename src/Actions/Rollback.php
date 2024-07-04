<?php

namespace NadLambino\Uploadable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use NadLambino\Uploadable\Dto\UploadOptions;

class Rollback
{
    private Model $model;

    private UploadOptions $options;

    private bool $forcedDelete = false;

    public function handle(Model $model, ?UploadOptions $options = null, bool $forcedDelete = false): void
    {
        $this->model = $model;
        $this->options = $options ?? new UploadOptions();
        $this->forcedDelete = $forcedDelete;

        if ($this->model->wasRecentlyCreated) {
            $this->deleteModel();
        } else {
            $this->undoChangesFromModel();
        }
    }

    private function deleteModel(): void
    {
        $isOnQueue = $this->options->uploadOnQueue !== null;

        if (
            ($isOnQueue && $this->options->deleteModelOnQueueUploadFail) ||
            (! $isOnQueue && $this->options->deleteModelOnUploadFail) ||
            $this->forcedDelete === true
        ) {
            DB::table($this->model->getTable())
                ->where($this->model->getKeyName(), $this->model->{$this->model->getKeyName()})
                ->delete();
        }
    }

    private function undoChangesFromModel(): void
    {
        if (empty($this->options->originalAttributes)) {
            return;
        }

        $isOnQueue = $this->options->uploadOnQueue !== null;

        if (
            ($isOnQueue && $this->options->rollbackModelOnQueueUploadFail) ||
            (! $isOnQueue && $this->options->rollbackModelOnUploadFail)
        ) {
            $this->model->fresh()
                ->forceFill($this->options->originalAttributes)
                ->updateQuietly();
        }
    }
}
