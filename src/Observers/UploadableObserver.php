<?php

namespace NadLambino\Uploadable\Observers;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Uploadable;

readonly class UploadableObserver
{
    public function __construct(private Uploadable $uploadable)
    {
    }

    /**
     * @throws Exception
     */
    public function created(Model $model) : void
    {
        $fullPath = null;

        try {
            DB::beginTransaction();

            /** @var UploadedFile $file */
            if (method_exists($model, 'getUploads')) {
                $files = $model->getUploads();

                foreach ($files as $file) {
                    if (is_array($file)) {
                        $this->uploads($file, $model);
                        continue;
                    }

                    $this->upload($file, $model);
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            if ($fullPath) {
                $this->uploadable->delete($fullPath);
            }

            $this->deleteQuitely($model);

            throw $exception;
        }
    }

    private function uploads(array $files, Model $model)
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
        // Before uploading the file, we will run the `beforeUpload` method
        // to do whatever the uploadable wants to do with the UploadedFile file
        // and the Uploadable Model.
        $model->beforeUpload($file, $model);

        $path = $model->getUploadPath($file, $model);
        $filename = $model->getUploadFilename($file, $model);

        $fullpath = $this->uploadable->upload($file, $path, $filename);

        $upload = new Upload();
        $upload->path = $fullpath;
        $upload->name = $filename;
        $upload->original_name = $file->getClientOriginalName();
        $upload->extension = strtolower($file->getClientOriginalExtension());
        $upload->size = $file->getSize();
        $upload->type = $file->getMimeType();

        // After uploading the file and before saving, we will run the `afterUpload` method
        // to do whatever the uploadable wants to do with the Upload model, UploadedFile file,
        // Uploadable Model, and the full path to the file.
        $this->afterUpload($upload, $file, $model, $fullpath);

        $upload->uploadable()->associate($model);
        $upload->save();
    }

    private function afterUpload(Upload $upload, UploadedFile $file, Model $model, string $fullpath) : void
    {
        $class = get_class($model);

        if ($class::$afterUploadCallback instanceof Closure) {
            $callback = $class::$afterUploadCallback;
            $callback($upload, $file, $model, $fullpath);
        } else {
            $model->afterUpload($upload, $file, $model, $fullpath);
        }
    }

    /**
     * @throws Exception
     */
    public function deleted($model) : void
    {
        try {
            DB::beginTransaction();

            if (($path = $model->upload?->path) && $this->uploadable->delete($path)) {
                $model->upload?->delete();

                DB::commit();
            }
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    /**
     * Delete the model without firing any events
     *
     * @param Model $model
     * @return void
     */
    private function deleteQuitely(Model $model) : void
    {
        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->{$model->getKeyName()})
            ->delete();
    }
}
