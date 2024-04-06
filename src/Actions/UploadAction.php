<?php

namespace NadLambino\Uploadable\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Uploadable;

class UploadAction
{
    private Request $request;

    public function __construct(private readonly Uploadable $uploadable)
    {
    }

    public function handle(array $files, Model $model, Collection $request) : void
    {
        $this->request = new Request($request->all());

        foreach ($files as $file) {
            if (is_array($file)) {
                $this->uploads($file, $model);
                continue;
            }

            $this->upload($file, $model);
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

    private function upload(UploadedFile | string $file, Model $model) : void
    {
        $isFilePath = is_string($file);
        $filePath = $isFilePath ? $file : null;

        if ($isFilePath) {
            $root = config('filesystems.disks.local.root');
            $file = new UploadedFile($root . DIRECTORY_SEPARATOR . $filePath, basename($filePath));
        }

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

        if ($isFilePath && $filePath) {
            Storage::delete($filePath);
        }
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
}
