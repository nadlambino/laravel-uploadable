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
        $fullPath = null;

        try {
            DB::beginTransaction();
            /** @var UploadedFile $file */
            if (method_exists($model, 'getFileUpload') && ($file = $model->getFileUpload())) {
                $path = $model->getTable() . DIRECTORY_SEPARATOR . $model->id;
                $name = $file->getClientOriginalName();
                $fullPath = $this->uploadable->upload($file, $path, $name);

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
            DB::rollBack();
            if ($fullPath) {
                $this->uploadable->delete($fullPath);
            }
            $model->delete();
            throw $exception;
        }
    }

    public function deleted($model) : void
    {
        try {
            DB::beginTransaction();
            if ($this->uploadable->delete($model->file->path)) {
                $model->file?->delete();

                DB::commit();
            }
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
