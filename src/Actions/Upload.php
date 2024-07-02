<?php

namespace NadLambino\Uploadable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use NadLambino\Uploadable\Contracts\StorageContract;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;

class Upload
{
    protected Model $uploadable;
    protected array $options = [];
    protected array $fullpaths = [];
    protected array $uploadIds = [];

    public function __construct(protected StorageContract $storage)
    {

    }

    public function handle(array|UploadedFile $files, Model $uploadable, array $options = [])
    {
        $this->uploadable = $uploadable;
        $this->options = $options;

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        $this->uploads($files);

        // TODO: Delete previous uploads when required
    }

    protected function uploads(array $files)
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->uploads($file);
            } else {
                $this->upload($file);
            }
        }
    }

    protected function upload(UploadedFile|string $file)
    {
        try {
            DB::beginTransaction();

            $uploadedFile = $file instanceof UploadedFile ? $file : $this->getUploadedFile($file);

            $path = $this->uploadable->getUploadPath($uploadedFile);
            $filename = $this->uploadable->getUploadFilename($uploadedFile);

            $fullpath = $this->storage->upload($uploadedFile, $path, $filename);
            $this->fullpaths[] = $fullpath;

            $upload = new ModelsUpload();
            $upload->path = $fullpath;
            $upload->name = $filename;
            $upload->original_name = $uploadedFile->getClientOriginalName();
            $upload->extension = strtolower($uploadedFile->getClientOriginalExtension());
            $upload->size = $uploadedFile->getSize();
            $upload->type = $uploadedFile->getMimeType();

            // TODO: Call to user-defined callback before saving the upload

            $upload->uploadable()->associate($this->uploadable);
            $upload->save();

            // TOOD: Call to delete temporary files if there are any

            DB::commit();

            $this->uploadIds[] = $upload->id;
        } catch (\Exception $exception) {
            DB::rollBack();

            // TODO: Call to delete all the uploaded files when an error occurs
            // TODO: Call to rollback all the model changes when an error occurs

            throw $exception;
        }
    }

    protected function getUploadedFile(string $file) : UploadedFile
    {
        $tempDisk = config('uploadable.temp_disk', 'local');
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root . DIRECTORY_SEPARATOR . $file, basename($file));
    }
}
