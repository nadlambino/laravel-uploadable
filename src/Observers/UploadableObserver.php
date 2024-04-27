<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;
use NadLambino\Uploadable\Actions\UploadAction;
use NadLambino\Uploadable\Exceptions\UnserializableException;

readonly class UploadableObserver
{
    public function __construct(private UploadAction $uploadAction)
    {
    }

    /**
     * Handle the Model "created" event.
     *
     * @throws Exception
     */
    public function created(Model $model) : void
    {
        $this->processUploads($model, true);
    }

    /**
     * Handle the Model "updated" event.
     *
     * @throws Exception
     */
    public function updated(Model $model) : void
    {
        $this->processUploads($model, false);
    }

    /**
     * Process the uploads.
     *
     * @param Model $model             The model that owns the uploads.
     * @param bool  $deleteModelOnFail Whether to delete the model if the upload fails.
     *
     * @throws Exception
     */
    private function processUploads(Model $model, bool $deleteModelOnFail) : void
    {
        try {
            $options = $this->getOptions($model);
            if ($options['dont_upload'] === true) {
                return;
            }

            /** @var UploadedFile[] $uploads */
            $uploads = $model->getUploads();

            if ($options['queue'] !== null) {
                $paths = $this->uploadTempFiles($uploads);

                $this->handleQueuedUploads($model, $paths, $options);
            } else {
                $this->uploadAction->handle($uploads, $model, $options);
            }
        } catch (Exception $exception) {
            // If the upload fails because of validation or serialization error, rollback the model changes.
            // The upload action already does its own rollback and we don't want to do it twice.
            // So we only call the rollback if the exception is validation or serialization error.
            if ($exception instanceof ValidationException || $exception instanceof UnserializableException) {
                $this->uploadAction->rollbackModelChanges($model, $deleteModelOnFail);
            }

            throw $exception;
        }
    }

    /**
     * Get the options for the upload process.
     * This includes the options that were statically set in the uploadable model which won't be included when the
     * model is serialized. This is specifically useful when the upload process is queued.
     *
     * @param Model $model The model that owns the uploads.
     *
     * @return array The options for the upload process.
     */
    private function getOptions(Model $model) : array
    {
        $class = get_class($model);

        return [
            'original_attributes' => $model->getOriginal(),
            'queue' => $class::$uploadOnQueue ?? config('uploadable.upload_on_queue_using'),
            'after_upload_using' => $class::$afterUploadCallback ?? null,
            'dont_upload' => $class::$dontUpload ?? false,
            'delete_previous_uploads' => $class::$deletePreviousUploads ?? config('uploadable.delete_previous_uploads', false),
            'delete_model_on_upload_fail' => config('uploadable.delete_model_on_upload_fail', true),
            'delete_model_on_queue_upload_fail' => config('uploadable.delete_model_on_queue_upload_fail', false),
            'rollback_model_on_upload_fail' => config('uploadable.rollback_model_on_upload_fail', true),
            'rollback_model_on_queue_upload_fail' => config('uploadable.rollback_model_on_queue_upload_fail', false),
        ];
    }

    /**
     * Upload the temporary files.
     *
     * @param UploadedFile[] $files The files to upload.
     *
     * @return string[] The paths where the files were stored.
     */
    private function uploadTempFiles(array $files) : array
    {
        $paths = [];

        foreach ($files as $file) {
            if (is_array($file)) {
                $paths = array_merge($paths, $this->uploadTempFiles($file));
                continue;
            }

            $path = $file->store('tmp', config('uploadable.temp_disk', 'local'));

            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * Handles the queued upload.
     *
     * @param Model $model The model that owns the uploads.
     * @param array $paths The paths to upload.
     * @param array $options The options for the upload process.
     *
     * @return void
     * @throws UnserializableException
     */
    private function handleQueuedUploads(Model $model, array $paths, array $options) : void
    {
        try {
            ProcessUploadJob::dispatch($paths, $model, $options)->onQueue($options['queue']);
        } catch (Exception $exception) {
            throw new UnserializableException($exception->getMessage());
        }
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @throws Exception
     */
    public function deleted($model) : void
    {
        try {
            DB::beginTransaction();

            $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';
            $model->uploads()->get()->each(fn($upload) => $upload->$deleteMethod());

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
