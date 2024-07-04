<?php

namespace NadLambino\Uploadable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Contracts\StorageContract;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;

class Upload
{
    /**
     * The uploadable model.
     */
    private Model $uploadable;

    /**
     * Combination of uploadable config and other options.
     */
    private UploadOptions $options;

    /**
     * The full paths of the uploaded files.
     */
    private array $fullpaths = [];

    /**
     * The ids of the uploaded files.
     */
    private array $uploadIds = [];

    public function __construct(private StorageContract $storage) {}

    /**
     * Handle the upload process.
     *
     * @param  array|UploadedFile|string  $files  The files to upload.
     *                                            Can be an array of files or a single file.
     *                                            File can be an instance of Illuminate\Http\UploadedFile or a full path to a file uploaded on the temporary disk.
     * @param  Model  $uploadable  The model to associate the uploads with.
     * @param  ?UploadOptions  $options  Combination of uploadable config and other options.
     */
    public function handle(array|UploadedFile|string $files, Model $uploadable, ?UploadOptions $options = null): void
    {
        $this->uploadable = $uploadable;
        $this->options = $options ?? app(UploadOptions::class);

        if ($this->options->disableUpload === true) {
            return;
        }

        if (! is_array($files)) {
            $files = [$files];
        }

        $this->uploads($files);

        $this->deletePreviousUploads();
    }

    /**
     * Upload files.
     *
     * @param  array  $files  The files to upload.
     */
    private function uploads(array $files): void
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->uploads($file);
            } else {
                $this->upload($file);
            }
        }
    }

    /**
     * Upload a single file.
     *
     * @param  UploadedFile|string  $file  The file to upload.
     */
    private function upload(UploadedFile|string $file): void
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

            $this->beforeSavingUpload($upload);

            $upload->uploadable()->associate($this->uploadable);
            $upload->save();

            $this->deleteTemporaryFile($file);

            DB::commit();

            $this->uploadIds[] = $upload->id;
        } catch (\Exception $exception) {
            DB::rollBack();

            $this->deleteUploadedFilesFromStorage();

            /** @var Rollback $rollback */
            $rollback = app(Rollback::class);
            $rollback->handle($this->uploadable, $this->options);

            throw $exception;
        }
    }

    /**
     * Get an instance of Illuminate\Http\UploadedFile from a full path.
     *
     * @param  string  $file  The file to get and wrapped into an instance of Illuminate\Http\UploadedFile.
     */
    private function getUploadedFile(string $file): UploadedFile
    {
        $tempDisk = $this->options->temporaryDisk;
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root.DIRECTORY_SEPARATOR.$file, basename($file));
    }

    /**
     * Callback before saving the upload.
     *
     * @param  ModelsUpload  $upload  The upload model.
     */
    private function beforeSavingUpload(ModelsUpload $upload): void
    {
        $callback = $this->options->beforeSavingUploadUsing;

        if ($callback instanceof SerializableClosure) {
            $callback($upload, $this->uploadable);
        } else {
            $this->uploadable->beforeSavingUpload($upload, $this->uploadable);
        }
    }

    /**
     * Delete the temporary file.
     *
     * @param  UploadedFile|string  $file  The file to delete.
     */
    private function deleteTemporaryFile(UploadedFile|string $file): void
    {
        if (($file instanceof UploadedFile) === false) {
            Storage::disk($this->options->temporaryDisk)->delete($file);
        }
    }

    /**
     * Delete the uploaded files from the storage.
     */
    private function deleteUploadedFilesFromStorage(): void
    {
        foreach ($this->fullpaths as $fullpath) {
            $this->storage->delete($fullpath);
        }
    }

    /**
     * Delete the previous uploads.
     */
    private function deletePreviousUploads(): void
    {
        $deleteMethod = $this->options->forceDeleteUploads === true ? 'forceDelete' : 'delete';

        if ($this->options->replacePreviousUploads && count($this->uploadIds) > 0) {
            $this->uploadable->uploads()
                ->whereNotIn('id', $this->uploadIds)
                ->get()
                ->each(fn ($upload) => $upload->$deleteMethod());
        }
    }
}
