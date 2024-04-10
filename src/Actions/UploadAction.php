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
        $this->options = $options;

        $this->uploads($files, $model);
        $this->deletePreviousUploads($model);
    }

    /**
     * Upload the files.
     *
     * @param array $files The files to upload.
     * @param Model $model The model that owns the uploads.
     *
     * @return void
     * @throws Exception
     */
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

    /**
     * Upload the file.
     *
     * @param UploadedFile|string $file  The file to upload.
     * @param Model               $model The model that owns the upload.
     *
     * @return void
     * @throws Exception
     */
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
            $model->saveQuietly();

            $this->deleteTempFile($file);

            DB::commit();

            $this->uploadedFileIds[] = $upload->id;
        } catch (Exception $exception) {
            DB::rollBack();
            $this->deleteUploadedFiles();
            $this->undoModelChanges($model);

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
     * @param Model  $model  The model that owns the upload.
     *
     * @return void
     * @throws PhpVersionNotSupportedException
     */
    private function afterUpload(Upload $upload, Model $model) : void
    {
        $callback = Arr::get($this->options, 'afterUploadUsing');
        if ($callback instanceof SerializableClosure || $callback instanceof Closure) {
            $callback($upload, $model);
        } else {
            $model->afterUpload($upload, $model);
        }
    }

    /**
     * Delete all the previous uploads from the database and storage.
     *
     * @param Model $model The model that owns the uploads to delete.
     *
     * @return void
     */
    private function deletePreviousUploads(Model $model) : void
    {
        $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';

        if (Arr::get($this->options, 'deletePreviousUploads', false) && count($this->uploadedFileIds) > 0) {
            $model->uploads()
                ->whereNotIn('id', $this->uploadedFileIds)
                ->get()
                ->each(function (Upload $upload) use ($deleteMethod) {
                    $this->uploadable->delete($upload->path);
                    $upload->$deleteMethod();
                });
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
     * Undo the changes made to the model.
     * If the model was just created, it will be deleted.
     * If the model was updated, it will be updated with the original attributes.
     *
     * @param Model $model  The model to undo changes or to delete.
     * @param bool  $forced Force delete the model if it was just created.
     *
     * @return void
     */
    public function undoModelChanges(Model $model, bool $forced = false) : void
    {
        if ($model->wasRecentlyCreated === false) {
            $this->undoChangesFromUploadableModel($model);
            return;
        }

        $this->deleteUploadableModel($model, $forced);
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
