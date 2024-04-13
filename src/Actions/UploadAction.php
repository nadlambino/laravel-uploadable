<?php

namespace NadLambino\Uploadable\Actions;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Models\Upload;
use NadLambino\Uploadable\Uploadable;

class UploadAction
{
    /**
     * The model that owns the uploads.
     *
     * @var Model $model
     */
    private Model $model;

    /**
     * The full paths of the uploaded files.
     *
     * @var array $uploadedFullpaths
     */
    private array $uploadedFullpaths = [];

    /**
     * The ids of the uploaded files.
     *
     * @var array $uploadedFileIds
     */
    private array $uploadedFileIds = [];

    /**
     * The options for the upload process.
     *
     * @var array $options
     */
    private array $options = [];

    public function __construct(private readonly Uploadable $uploadable)
    {
    }

    /**
     * Handle the upload process.
     *
     * @param array $files   The files to upload.
     * @param Model $model   The model that owns the uploads.
     * @param array $options The options for the upload process.
     *
     * @return void
     * @throws Exception
     */
    public function handle(array $files, Model $model, array $options = []) : void
    {
        $this->model = $model;
        $this->options = $options;

        $this->uploads($files);
        $this->deletePreviousUploads();
    }

    /**
     * Upload the files.
     *
     * @param array $files The files to upload.
     *
     * @return void
     * @throws Exception
     */
    private function uploads(array $files) : void
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
     * Upload the file.
     *
     * @param UploadedFile|string $file The file to upload.
     *
     * @return void
     * @throws Exception
     */
    private function upload(UploadedFile | string $file) : void
    {
        try {
            DB::beginTransaction();

            $uploadedFileInstance = $this->getUploadedFileInstance($file);

            $path = $this->model->getUploadPath($uploadedFileInstance);
            $filename = $this->model->getUploadFilename($uploadedFileInstance);

            $fullpath = $this->uploadable->upload($uploadedFileInstance, $path, $filename);
            $this->uploadedFullpaths[] = $fullpath;

            $upload = new Upload();
            $upload->path = $fullpath;
            $upload->name = $filename;
            $upload->original_name = $uploadedFileInstance->getClientOriginalName();
            $upload->extension = strtolower($uploadedFileInstance->getClientOriginalExtension());
            $upload->size = $uploadedFileInstance->getSize();
            $upload->type = $uploadedFileInstance->getMimeType();

            $this->afterUpload($upload);

            $upload->uploadable()->associate($this->model);
            $upload->save();
            $this->model->saveQuietly();

            $this->deleteTempFile($file);

            DB::commit();

            $this->uploadedFileIds[] = $upload->id;
        } catch (Exception $exception) {
            DB::rollBack();
            $this->deleteUploadedFiles();
            $this->rollbackModelChanges($this->model);

            throw $exception;
        }
    }

    /**
     * Get the uploaded file instance.
     *
     * @param UploadedFile|string $file The file to get the instance.
     *
     * @return UploadedFile
     */
    private function getUploadedFileInstance(string | UploadedFile $file) : UploadedFile
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }

        $tempDisk = config('uploadable.temp_disk', 'local');
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root . DIRECTORY_SEPARATOR . $file, basename($file));
    }

    /**
     * Run the after upload callback.
     *
     * @param Upload $upload The upload model.
     *
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    private function afterUpload(Upload $upload) : void
    {
        $callback = Arr::get($this->options, 'after_upload_using');
        if ($callback instanceof SerializableClosure || $callback instanceof Closure) {
            $callback($upload, $this->model);
        } else {
            $this->model->afterUpload($upload, $this->model);
        }
    }

    /**
     * Delete all the previous uploads from the database and storage.
     *
     * @return void
     */
    private function deletePreviousUploads() : void
    {
        $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';

        if (Arr::get($this->options, 'delete_previous_uploads', false) && count($this->uploadedFileIds) > 0) {
            $this->model->uploads()
                ->whereNotIn('id', $this->uploadedFileIds)
                ->get()
                ->each(fn($upload) => $upload->$deleteMethod());
        }
    }

    /**
     * Delete the temporary file from the storage.
     *
     * @param UploadedFile|string $file The file to delete.
     *
     * @return void
     */
    private function deleteTempFile(UploadedFile | string $file) : void
    {
        if (($file instanceof UploadedFile) === false) {
            Storage::disk(config('uploadable.temp_disk', 'local'))->delete($file);
        }
    }

    /**
     * Delete all the uploaded files from the storage.
     *
     * @return void
     */
    private function deleteUploadedFiles() : void
    {
        foreach ($this->uploadedFullpaths as $fullpath) {
            $this->uploadable->delete($fullpath);
        }
    }

    /**
     * Rollback the changes made to the model.
     * If the model was just created, it will be deleted.
     * If the model was updated, it will be updated with the original attributes.
     *
     * @param Model $model  The model to undo changes or to delete.
     * @param bool  $forced Force delete the model if it was just created.
     *
     * @return void
     */
    public function rollbackModelChanges(Model $model, bool $forced = false) : void
    {
        if ($model->wasRecentlyCreated) {
            $this->deleteUploadableModel($model, $forced);
            return;
        }

        $this->undoChangesFromUploadableModel($model);
    }

    /**
     * Undo the changes made to the model.
     *
     * @param Model $model The model to undo changes.
     *
     * @return void
     */
    private function undoChangesFromUploadableModel(Model $model) : void
    {
        $attributes = $model->getOriginal();
        $model->fresh()
            ->forceFill($attributes)
            ->updateQuietly();
    }

    /**
     * Delete the uploadable model.
     *
     * @param Model $model  The model to delete.
     * @param bool  $forced Force delete the model.
     *
     * @return void
     */
    private function deleteUploadableModel(Model $model, bool $forced = false) : void
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
