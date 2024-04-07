<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NadLambino\Uploadable\Jobs\ProcessUploadJob;
use NadLambino\Uploadable\Actions\UploadAction;
use NadLambino\Uploadable\Uploadable;

class UploadableObserver
{
    private array $uploadedFullpaths = [];

    public function __construct(private readonly Uploadable $uploadable, private readonly UploadAction $uploadAction)
    {
    }

    /**
     * @throws Exception
     */
    public function created(Model $model) : void
    {
        try {
            DB::beginTransaction();

            /** @var UploadedFile[] $uploads */
            $uploads = $model->getUploads();
            $paths = [];
            $request = $this->createRequestCollection();

            if (($queue = config('uploadable.upload_on_queue_using')) !== null) {
                $paths = $this->uploadTempFiles($uploads);

                ProcessUploadJob::dispatch($paths, $model, $request)
                    ->onQueue($queue);
            } else {
                $this->uploadAction->handle($uploads, $model, $request);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            foreach ($this->uploadedFullpaths as $fullPath) {
                $this->uploadable->delete($fullPath);
            }

            $this->deleteQuietly($model);

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

            $path = $file->store('tmp');

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

    /**
     * Delete the model without firing any events
     *
     * @param Model $model
     *
     * @return void
     */
    private function deleteQuietly(Model $model) : void
    {
        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->{$model->getKeyName()})
            ->delete();
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
