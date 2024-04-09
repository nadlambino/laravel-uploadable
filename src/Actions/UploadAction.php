<?php

namespace NadLambino\Uploadable\Actions;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Uploadable;

class UploadAction
{
    private Request $request;

    private array $uploadedFullpaths = [];

    public function __construct(private readonly Uploadable $uploadable)
    {
    }

    public function handle(array $files, Model $model, Collection $request) : void
    {
        $this->request = new Request($request->all());

        $this->uploads($files, $model);
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

    private function upload(UploadedFile | string $file, Model $model) : void
    {
        try {
            DB::beginTransaction();

            $uploadedFileInstance = $this->getUploadedFileInstance($file);

            $path = $model->getUploadPath($uploadedFileInstance, $model);
            $filename = $model->getUploadFilename($uploadedFileInstance, $model);

            $fullpath = $this->uploadable->upload($uploadedFileInstance, $path, $filename);
            $this->uploadedFullpaths[] = $fullpath;

            $upload = new Upload();
            $upload->path = $fullpath;
            $upload->name = $filename;
            $upload->original_name = $uploadedFileInstance->getClientOriginalName();
            $upload->extension = strtolower($uploadedFileInstance->getClientOriginalExtension());
            $upload->size = $uploadedFileInstance->getSize();
            $upload->type = $uploadedFileInstance->getMimeType();

            $this->afterUpload($upload, $model);

            $upload->uploadable()->associate($model);
            $upload->save();
            $model->save();

            $this->deleteTempFile($file);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            $this->deleteUploadedFiles();
            $this->deleteModelQuietly($model);

            throw $exception;
        }
    }

    private function getUploadedFileInstance(string | UploadedFile $file) : UploadedFile
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }

        $tempDisk = config('uploadable.temp_disk', 'local');
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root . DIRECTORY_SEPARATOR . $file, basename($file));
    }

    private function afterUpload(Upload $upload, Model $model) : void
    {
        $class = get_class($model);

        if ($class::$afterUploadCallback instanceof Closure) {
            $callback = $class::$afterUploadCallback;
            $callback($upload, $model);
        } else {
            $model->afterUpload($upload, $model, $this->request);
        }
    }

    private function deleteTempFile(UploadedFile | string $file) : void
    {
        if (($file instanceof UploadedFile) === false) {
            Storage::disk(config('uploadable.temp_disk', 'local'))->delete($file);
        }
    }

    private function deleteUploadedFiles() : void
    {
        foreach ($this->uploadedFullpaths as $fullpath) {
            $this->uploadable->delete($fullpath);
        }
    }

    public function deleteModelQuietly(Model $model, bool $forced = false) : void
    {
        $isOnQueue = config('uploadable.upload_on_queue_using') !== null;

        if (
            ($isOnQueue && config('uploadable.delete_model_on_queue_upload_fail') === true) ||
            (! $isOnQueue && config('uploadable.delete_model_on_upload_fail') === true) ||
            $forced === true
        ) {
            DB::table($model->getTable())
                ->where($model->getKeyName(), $model->{$model->getKeyName()})
                ->delete();
        }
    }
}
