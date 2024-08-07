<?php

namespace NadLambino\Uploadable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Contracts\StorageContract;
use NadLambino\Uploadable\Dto\UploadOptions;
use NadLambino\Uploadable\Events\AfterUpload;
use NadLambino\Uploadable\Events\BeforeUpload;
use NadLambino\Uploadable\Events\CompleteUpload;
use NadLambino\Uploadable\Events\FailedUpload;
use NadLambino\Uploadable\Events\StartUpload;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;

final class Upload
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

    /**
     * The models that should be ignored during the upload process.
     */
    public static array $disabledModels = [];

    /**
     * The only allowed models to process the file uploads.
     */
    public static array $onlyModels = [];

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
        $this->storage = $this->options->disk ?
            $this->storage->disk($this->options->disk) :
            $this->storage;

        $this->setDisabledModelsWhenOnQueue();
        $this->setEnabledModelsWhenOnQueue();

        if ($this->shouldNotProceed()) {
            return;
        }

        if (! is_array($files)) {
            $files = [$files];
        }

        BeforeUpload::dispatch($this->uploadable, $files, $this->options);

        $this->uploads($files);

        $this->deletePreviousUploads();

        CompleteUpload::dispatch($this->uploadable, $this->uploadable->uploads()->whereIn('id', $this->uploadIds)->get());
    }

    /**
     * Disable the upload process for the given models.
     */
    public static function disableFor(string|array|Model $models): void
    {
        self::removeFrom($models, self::$onlyModels);

        self::$disabledModels = array_unique(is_array($models) ? $models : [$models]);
    }

    /**
     * Enable the upload process for the given models.
     */
    public static function enableFor(string|array|Model $models): void
    {
        self::removeFrom($models, self::$disabledModels);
    }

    /**
     * Only process the upload for the given models.
     */
    public static function onlyFor(string|array|Model $models): void
    {
        self::removeFrom($models, self::$disabledModels);

        self::$onlyModels = is_array($models) ? $models : [$models];
    }

    /**
     * Remove the given models from the list.
     */
    private static function removeFrom(string|array|Model $models, array &$list): void
    {
        if ($models instanceof Model) {
            $list = array_filter($list, fn ($model) => ! $model instanceof $models || $model->getKey() !== $models->getKey());
        } else {
            $list = array_diff($list, is_array($models) ? $models : [$models]);
        }
    }

    /**
     * Set the models that should be ignored during the upload process on queue.
     */
    private function setDisabledModelsWhenOnQueue(): void
    {
        if (! is_null($this->options->uploadOnQueue)) {
            self::$disabledModels = $this->options->disabledModels;
        }
    }

    /**
     * Set the only allowed models to process the file uploads on queue.
     */
    private function setEnabledModelsWhenOnQueue(): void
    {
        if (! is_null($this->options->uploadOnQueue)) {
            self::$onlyModels = $this->options->onlyModels;
        }
    }

    /**
     * Check if the upload process should not proceed.
     */
    private function shouldNotProceed(): bool
    {
        return
            // Should not proceed if the upload is disabled.
            $this->options->disableUpload === true ||

            // Should not proceed if the uploadable model class is included from the list of disabled models.
            in_array(get_class($this->uploadable), self::$disabledModels) ||

            // Should not proceed if the uploadable model instance is included from the list of disabled models.
            collect(self::$disabledModels)->first(function ($model) {
                return $model instanceof $this->uploadable && $model->getKey() === $this->uploadable->getKey();
            }) !== null ||

            // Should not proceed if the $onlyModels is not empty and the uploadable model is not included from the list of enabled models.
            ! $this->isUploadableModelEnabled();
    }

    /**
     * Check if the uploadable model is included from the list of enabled models.
     */
    private function isUploadableModelEnabled(): bool
    {
        return
            // The given uploadable model is considered as enabled when the $onlyModels is empty.
            empty(self::$onlyModels) ||

            // The given uploadable model is considered as enabled when the uploadable model class is included from the list of enabled models.
            in_array(get_class($this->uploadable), self::$onlyModels) ||

            // The given uploadable model is considered as enabled when the uploadable model instance is included from the list of enabled models.
            collect(self::$onlyModels)->first(function ($model) {
                return $model instanceof $this->uploadable && $model->getKey() === $this->uploadable->getKey();
            }) !== null;
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
            $storageOptions = $this->options->uploadStorageOptions ?? [];

            StartUpload::dispatch($this->uploadable, $filename, $path);

            $fullpath = $this->storage->upload($uploadedFile, $path, $filename, $storageOptions);
            $this->fullpaths[] = $fullpath;

            /** @var ModelsUpload $upload */
            $upload = new $this->options->uploadModelClass;
            $upload->path = $fullpath;
            $upload->name = $filename;
            $upload->original_name = $uploadedFile->getClientOriginalName();
            $upload->extension = strtolower($uploadedFile->getClientOriginalExtension());
            $upload->size = $uploadedFile->getSize();
            $upload->type = $uploadedFile->getMimeType();
            $upload->disk = $this->options->disk ?? config('filesystems.default');

            $this->assignUploadAttributes($upload);
            $this->beforeSavingUpload($upload);

            $upload->uploadable()->associate($this->uploadable);
            $upload->save();

            $this->deleteTemporaryFile($file);

            DB::commit();

            $this->uploadIds[] = $upload->id;

            AfterUpload::dispatch($this->uploadable, $upload->fresh());
        } catch (\Exception $exception) {
            DB::rollBack();

            $this->deleteUploadedFilesFromStorage();

            /** @var Rollback $rollback */
            $rollback = app(Rollback::class);
            $rollback->handle($this->uploadable, $this->options);

            FailedUpload::dispatch($exception, $this->uploadable);

            throw $exception;
        }
    }

    private function assignUploadAttributes(Model $upload): void
    {
        collect($this->options->uploadAttributes)->each(function ($value, $key) use ($upload) {
            $upload->$key = $value;
        });
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
     * @param  Model  $upload  The upload model.
     */
    private function beforeSavingUpload(Model $upload): void
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
