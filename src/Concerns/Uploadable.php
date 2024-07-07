<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Http\UploadedFile;

trait Uploadable
{
    use Handlers, Options, Relations, Validation;

    /**
     * The files that should be uploaded.
     */
    protected array $uploadFrom = [];

    /**
     * Self boot this trait.
     */
    public static function bootUploadable(): void
    {
        static::replacePreviousUploads(static::$replacePreviousUploads ?? config('uploadable.replace_previous_uploads', false));
        static::validateUploads(static::$validateUploads ?? config('uploadable.validate', true));
        static::uploadOnQueue(static::$uploadOnQueue ?? config('uploadable.upload_on_queue', null));
        static::uploadStorageOptions(static::$uploadStorageOptions);
        static::created(fn ($model) => $model->handleCreated($model));
        static::updated(fn ($model) => $model->handleUpdated($model));
        static::deleted(fn ($model) => $model->handleDeleted($model));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => $model->handleRestored($model));
        }
    }

    /**
     * Get the upload filename.
     */
    public function getUploadFilename(UploadedFile $file): string
    {
        return str_replace('.', '', microtime(true)).'-'.$file->hashName();
    }

    /**
     * Get the upload path.
     */
    public function getUploadPath(UploadedFile $file): string
    {
        return $this->getTable().DIRECTORY_SEPARATOR.$this->{$this->getKeyName()};
    }

    /**
     * Get the options for uploading the file in the storage.
     */
    public function getUploadStorageOptions(): array
    {
        return [];
    }

    /**
     * You can manually create the uploads when you are sure that the `created` event
     * was not triggered. Otherwise, calling this method might duplicate your uploads.
     */
    public function createUploads(): void
    {
        static::$disableUpload = false;

        $this->handleCreated($this);
    }

    /**
     * You can manually update the uploads when you are sure that the `updated` event
     * was not triggered. Otherwise, calling this method might duplicate your uploads.
     */
    public function updateUploads(): void
    {
        static::$disableUpload = false;

        $this->handleUpdated($this);
    }

    /**
     * Get the files that should be uploaded.
     */
    public function getUploads(): array
    {
        $files = ! empty($this->uploadFrom) ?
            $this->uploadFrom :
            $this->getFilesFromRequest();

        return static::$uploadOnQueue ?
            $this->uploadFilesTemporarily($files) :
            $files;
    }

    /**
     * Set the files that should be uploaded. This can be use on a Livewire
     * component where the request object doesn't contain the UploadedFiles.
     * Note that the validation will not be perform on these files, so make
     * sure that they are already validated. Livewire provides a way to do
     * the validation by using Validation attributes
     *
     * @param  array|UploadedFile  $files  The files to upload.
     *                                     Can be a single or an array of files.
     *                                     File can be an instance of Illuminate\Http\UploadedFile
     *                                     or a full path to a file uploaded on the temporary disk.
     * @return $this
     */
    public function uploadFrom(array|UploadedFile|string $files): static
    {
        $this->uploadFrom = is_array($files) ? $files : [$files];

        return $this;
    }

    /**
     * Get the files from the request.
     */
    private function getFilesFromRequest(): array
    {
        $rules = $this->getUploadRules();

        /** @var \Illuminate\Http\Request $request */
        $request = app('request');

        if (static::$validateUploads) {
            $validatable = array_filter($rules, fn ($value) => ! empty($value));
            $request->validate($validatable, $this->uploadRuleMessages());
        }

        $fields = collect($rules)
            ->keys()
            ->zip($rules)
            ->map(fn ($pair) => is_numeric($pair[0]) ? $pair[1] : $pair[0])
            ->all();

        return $request->only($fields);
    }

    /**
     * Upload files in the temporary disk. This is used when the upload process
     * is done in the queue. UploadedFile instances are not serializable so they
     * need to be stored in a temporary disk.
     *
     * @param  array  $files  The files to upload.
     */
    private function uploadFilesTemporarily(array $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            if (is_array($file)) {
                $paths = array_merge($paths, $this->uploadFilesTemporarily($file));

                continue;
            }

            if (! ($file instanceof UploadedFile)) {
                $paths[] = $file;

                continue;
            }

            $path = $file->store('tmp', config('uploadable.temporary_disk', 'local'));

            $paths[] = $path;
        }

        return $paths;
    }
}
