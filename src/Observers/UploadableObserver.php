<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;
use NadLambino\Uploadable\Actions\UploadAction;
use NadLambino\Uploadable\Uploadable;

readonly class UploadableObserver
{
    public function __construct(private Uploadable $uploadable, private UploadAction $uploadAction)
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

            if (($queue = config('uploadable.upload_on_queue_using')) !== null) {
                $paths = $this->uploadTempFiles($uploads);

                ProcessUploadJob::dispatch($paths, $model, $options)->onQueue($queue);
            } else {
                $this->uploadAction->handle($uploads, $model, $options);
            }
        } catch (Exception $exception) {
            // If the upload fails because of validation errors, rollback the model changes.
            // Validation happens when we called the `getUploads` method.
            // The upload process from the upload action is already wrapped with a try-catch block
            // which does its own rollback. So we don't need to call the rollback here if it's not a validation exception.
            if ($exception instanceof ValidationException) {
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
            'delete_previous_uploads' => $class::$deletePreviousUploads ?? false,
            'after_upload_using' => $class::$afterUploadCallback ?? null,
            'dont_upload' => $class::$dontUpload ?? false,
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
