<?php

namespace NadLambino\Uploadable\Observers;

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
                    $path = $model->getTable() . DIRECTORY_SEPARATOR . $model->id;
                    $hashName = $file->hashName();
                    $fullPath = $this->uploadable->upload($file, $path, $hashName);

                    $upload = new Upload();
                    $upload->path = $fullPath;
                    $upload->name = $hashName;
                    $upload->original_name = $file->getClientOriginalName();
                    $upload->extension = $file->getClientOriginalExtension();
                    $upload->size = $file->getSize();
                    $upload->type = $file->getMimeType();

                    $upload->uploadable()->associate($model);
                    $upload->save();
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
