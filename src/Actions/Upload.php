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
    private Model $uploadable;

    private array $options = [];

    private array $fullpaths = [];

    private array $uploadIds = [];

    public function __construct(private StorageContract $storage) {}

    public function handle(array|UploadedFile|string $files, Model $uploadable, array $options = [])
    {
        $this->uploadable = $uploadable;
        $this->options = $options;

        if (! is_array($files)) {
            $files = [$files];
        }

        $this->uploads($files);

        $this->deletePreviousUploads();
    }

    private function uploads(array $files)
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                $this->uploads($file);
            } else {
                $this->upload($file);
            }
        }
    }

    private function upload(UploadedFile|string $file)
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

    private function getUploadedFile(string $file): UploadedFile
    {
        $tempDisk = config('uploadable.temporary_disk', 'local');
        $root = config("filesystems.disks.$tempDisk.root");

        return new UploadedFile($root.DIRECTORY_SEPARATOR.$file, basename($file));
    }

    private function beforeSavingUpload(ModelsUpload $upload)
    {
        $callback = data_get($this->options, 'before_saving_upload_using');

        if ($callback instanceof SerializableClosure || $callback instanceof \Closure) {
            $callback($upload, $this->uploadable);
        } else {
            $this->uploadable->beforeSavingUpload($upload, $this->uploadable);
        }
    }

    private function deleteTemporaryFile(UploadedFile|string $file) : void
    {
        if (($file instanceof UploadedFile) === false) {
            Storage::disk(config('uploadable.temp_disk', 'local'))->delete($file);
        }
    }

    private function deleteUploadedFilesFromStorage(): void
    {
        foreach ($this->fullpaths as $fullpath) {
            $this->storage->delete($fullpath);
        }
    }

    public function rollbackModelChanges(Model $model, bool $forced = false): void
    {
        if ($model->wasRecentlyCreated) {
            $this->deleteUploadableModel($model, $forced);

            return;
        }

        $this->undoChangesFromUploadableModel($model);
    }

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
