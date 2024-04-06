<?php

namespace NadLambino\Uploadable\Observers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Uploadable;

class UploadableObserver
{
    private array $uploadedFullpaths = [];

    public function __construct(private readonly Uploadable $uploadable)
    {
    }

    /**
     * @throws Exception
     */
    public function created(Model $model) : void
    {
        try {
            DB::beginTransaction();

            $files = $model->getUploads();

            foreach ($files as $file) {
                if (is_array($file)) {
                    $this->uploads($file, $model);
                    continue;
                }

                $this->upload($file, $model);
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

    private function uploads(array $files, Model $model) : void
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->uploads($file, $model);
            } else {
                $this->upload($file, $model);
            }
        }
    }

    private function upload(UploadedFile $file, Model $model) : void
    {
        $path = $model->getUploadPath($file, $model);
        $filename = $model->getUploadFilename($file, $model);

        $fullpath = $this->uploadable->upload($file, $path, $filename);
        $this->uploadedFullpaths[] = $fullpath;

        $upload = new Upload();
        $upload->path = $fullpath;
        $upload->name = $filename;
        $upload->original_name = $file->getClientOriginalName();
        $upload->extension = strtolower($file->getClientOriginalExtension());
        $upload->size = $file->getSize();
        $upload->type = $file->getMimeType();

        // After uploading the file and before saving the uploads data, we will run the `afterUpload` method
        // to do whatever you want to do with the Upload model and Uploadable Model.
        $this->afterUpload($upload, $model);

        $upload->uploadable()->associate($model);
        $upload->save();
        $model->save();
    }

    private function afterUpload(Upload $upload, Model $model) : void
    {
        $class = get_class($model);

        if ($class::$afterUploadCallback instanceof Closure) {
            $callback = $class::$afterUploadCallback;
            $callback($upload, $model);
        } else {
            $model->afterUpload($upload, $model);
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
