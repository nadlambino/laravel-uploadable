<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
     * @throws Exception
     */
    public function created(Model $model) : void
    {
        $this->processUploads($model, true);
    }

    public function updated(Model $model) : void
    {
        $this->processUploads($model, false);
    }

    private function processUploads(Model $model, bool $deleteModelOnFail) : void
    {
        try {
            DB::beginTransaction();

            /** @var UploadedFile[] $uploads */
            $uploads = $model->getUploads();
            $request = $this->createRequestCollection();

            if (($queue = config('uploadable.upload_on_queue_using')) !== null) {
                $paths = $this->uploadTempFiles($uploads);

                ProcessUploadJob::dispatch($paths, $model, $request)->onQueue($queue);
            } else {
                $this->uploadAction->handle($uploads, $model, $request);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            $this->uploadAction->undoModelChanges($model, $exception instanceof ValidationException && $deleteModelOnFail);

            throw $exception;
        }
    }

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

    private function createRequestCollection() : Collection
    {
        /** @var Request $request */
        $request = request();

        foreach ($request->files as $key => $file) {
            $request->files->remove($key);
        }

        $this->removeFilesFromRequest($request->all(), $request);

        return collect($request->request->all());
    }

    private function removeFilesFromRequest(array $requestArray, Request $requestObject) : void
    {
        foreach ($requestArray as $key => $value) {
            if ($value instanceof UploadedFile) {
                $requestObject->request->remove($key);
            }

            if (is_array($value)) {
                $this->removeFilesFromRequest($value, $requestObject);
            }
        }
    }

    public function deleted($model) : void
    {
        try {
            DB::beginTransaction();

            if (config('uploadable.delete_uploads_on_model_delete') === true) {
                $paths = $model->uploads->pluck('path')->toArray();

                foreach ($paths as $path) {
                    $this->uploadable->delete($path);
                }
            }

            $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';
            $model->uploads()->$deleteMethod();

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
