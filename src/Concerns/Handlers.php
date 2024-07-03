<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Exceptions\UnserializableException;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;

trait Handlers
{
    protected function handleCreated(Model $model): void
    {
        $this->processUploads($model, true);
    }

    protected function handleUpdated(Model $model): void
    {
        $this->processUploads($model, false);
    }

    protected function handleDeleted(Model $model): void
    {
        try {
            DB::beginTransaction();

            $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';
            $model->uploads()->get()->each(fn ($upload) => $upload->$deleteMethod());

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    protected function handleRestored(Model $model): void
    {
        $model->uploads()->restore();
    }

    private function processUploads(Model $model, bool $deleteModelOnFail): void
    {
        try {
            /** @var \NadLambino\Uploadable\Dto\UploadOptions $options */
            $options = $this->getUploadOptions();

            $files = $this->getUploads();

            if ($options->uploadOnQueue) {
                $this->handleQueuedUpload($model, $files, $options);
            } else {
                /** @var \NadLambino\Uploadable\Actions\Upload $action */
                $action = app(Upload::class);

                $action->handle($files, $model, $options);
            }
        } catch (\Exception $exception) {
            // If the upload fails because of validation or serialization error, rollback the model changes.
            // The upload action already does its own rollback and we don't want to do it twice.
            // So we only call the rollback if the exception is validation or serialization error.
            if ($exception instanceof ValidationException || $exception instanceof UnserializableException) {
                $this->uploadAction->rollbackModelChanges($model, $deleteModelOnFail);
            }

            throw $exception;
        }
    }

    private function handleQueuedUpload(Model $model, array $files, UploadOptions $options): void
    {
        try {
            ProcessUploadJob::dispatch($files, $model, $options)->onQueue($options->uploadOnQueue);
        } catch (\Exception $exception) {
            throw new UnserializableException($exception->getMessage());
        }
    }
}
