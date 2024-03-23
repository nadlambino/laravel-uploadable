<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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
            if (method_exists($model, 'getUploadable') && ($file = $model->getUploadable())) {
                $path = $model->getTable() . DIRECTORY_SEPARATOR . $model->id;
                $name = $file->getClientOriginalName();
                $fullPath = $this->uploadable->upload($file, $path, $name);

                $model->uploadable()->create([
                    'path' => $this->uploadable->upload($file, $path, $name),
                    'name' => $name,
                    'original_name' => $name,
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ]);
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

            if (($path = $model->uploadable?->path) && $this->uploadable->delete($path)) {
                $model->uploadable?->delete();

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
