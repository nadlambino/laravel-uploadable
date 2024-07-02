<?php

namespace NadLambino\Uploadable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Contracts\StorageContract;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;

class Upload
{
    /**
     * The uploadable model.
     *
     * @var Model $uploadable
     */
    private Model $uploadable;

    /**
     * Combination of uploadable config and other options.
     *
     * @var array $options
     */
    private array $options = [];

    /**
     * The full paths of the uploaded files.
     *
     * @var array $fullpaths
     */
    private array $fullpaths = [];

    /**
     * The ids of the uploaded files.
     *
     * @var array $uploadIds
     */
    private array $uploadIds = [];

    public function __construct(private StorageContract $storage) { }

    /**
     * Handle the upload process.
     *
     * @param array|UploadedFile|string $files  The files to upload.
     *                                          Can be an array of files or a single file.
     *                                          File can be an instance of Illuminate\Http\UploadedFile or a full path to a file uploaded on the temporary disk.
     * @param Model $uploadable The model to associate the uploads with.
     * @param array $options    Combination of uploadable config and other options.
     *
     * @return void
     */
    public function handle(array|UploadedFile|string $files, Model $uploadable, array $options = []): void
    {
        $this->uploadable = $uploadable;
        $this->options = $options;

        if (! is_array($files)) {
            $files = [$files];
        }

        $this->uploads($files);

        $this->deletePreviousUploads();
    }

    /**
     * Upload files.
     *
     * @param array $files The files to upload.
     *
     * @return void
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
     * @param UploadedFile|string $file The file to upload.
     *
     * @return void
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
            $this->rollbackModelChanges($this->uploadable);

            throw $exception;
        }
    }

    /**
     * Get an instance of Illuminate\Http\UploadedFile from a full path.
     *
     * @param string $file The file to get and wrapped into an instance of Illuminate\Http\UploadedFile.
     *
     * @return UploadedFile
     */
    private function getUploadedFile(string $file): UploadedFile
    {
        $tempDisk = config('uploadable.temporary_disk', 'local');
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root.DIRECTORY_SEPARATOR.$file, basename($file));
    }

    /**
     * Callback before saving the upload.
     *
     * @param ModelsUpload $upload The upload model.
     *
     * @return void
     */
    private function beforeSavingUpload(ModelsUpload $upload): void
    {
        $callback = data_get($this->options, 'before_saving_upload_using');

        if ($callback instanceof SerializableClosure || $callback instanceof \Closure) {
            $callback($upload, $this->uploadable);
        } else {
            $this->uploadable->beforeSavingUpload($upload, $this->uploadable);
        }
    }

    /**
     * Delete the temporary file.
     *
     * @param UploadedFile|string $file The file to delete.
     *
     * @return void
     */
    private function deleteTemporaryFile(UploadedFile|string $file): void
    {
        if (($file instanceof UploadedFile) === false) {
            Storage::disk(config('uploadable.temporary_disk', 'local'))->delete($file);
        }
    }

    /**
     * Delete the uploaded files from the storage.
     *
     * @return void
     */
    private function deleteUploadedFilesFromStorage(): void
    {
        foreach ($this->fullpaths as $fullpath) {
            $this->storage->delete($fullpath);
        }
    }

    /**
     * Rollback the uploadable model's changes.
     *
     * @param Model $model The model to rollback.
     * @param bool  $forced Whether the rollback should be forced.
     *
     * @return void
     */
    public function rollbackModelChanges(Model $model, bool $forced = false): void
    {
        if ($model->wasRecentlyCreated) {
            $this->deleteUploadableModel($model, $forced);

            return;
        }

        $this->undoChangesFromUploadableModel($model);
    }

    /**
     * Delete the uploadable model.
     *
     * @param Model $model The model to delete.
     * @param bool  $forced Whether the deletion should be forced.
     *
     * @return void
     */
    private function deleteUploadableModel(Model $model, bool $forced = false): void
    {
        $isOnQueue = data_get($this->options, 'queue') !== null;

        if (
            ($isOnQueue && data_get($this->options, 'delete_model_on_queue_upload_fail')) ||
            (! $isOnQueue && data_get($this->options, 'delete_model_on_upload_fail')) ||
            $forced === true
        ) {
            DB::table($model->getTable())
                ->where($model->getKeyName(), $model->{$model->getKeyName()})
                ->delete();
        }
    }

    /**
     * Undo changes from the uploadable model.
     *
     * @param Model $model The model to undo changes from.
     *
     * @return void
     */
    private function undoChangesFromUploadableModel(Model $model): void
    {
        $isOnQueue = data_get($this->options, 'queue') !== null;

        if (
            ($isOnQueue && data_get($this->options, 'rollback_model_on_queue_upload_fail')) ||
            (! $isOnQueue && data_get($this->options, 'rollback_model_on_upload_fail'))
        ) {
            $model->fresh()
                ->forceFill(data_get($this->options, 'original_attributes'))
                ->updateQuietly();
        }
    }

    /**
     * Delete the previous uploads.
     *
     * @return void
     */
    private function deletePreviousUploads(): void
    {
        $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';

        if (data_get($this->options, 'replace_previous_uploads') && count($this->uploadIds) > 0) {
            $this->uploadable->uploads()
                ->whereNotIn('id', $this->uploadIds)
                ->get()
                ->each(fn($upload) => $upload->$deleteMethod());
        }
    }
}
