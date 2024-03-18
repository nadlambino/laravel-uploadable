<?php

namespace NadLambino\Uploadable\Observers;

use Exception;
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
    public function created($model) : void
    {
        try {
            DB::beginTransaction();
            /** @var UploadedFile $file */
            if (method_exists($model, 'getFileUpload') && ($file = $model->getFileUpload())) {
                $path = $model->getTable() . DIRECTORY_SEPARATOR . $model->id;
                $name = $file->getClientOriginalName();

                $model->file()->create([
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
            $model->delete();
            DB::rollBack();
            throw $exception;
        }
    }

    public function deleted($model) : void
    {
        info('UploadableObserver::deleted', ['model' => $model->toArray()]);
    }
}
