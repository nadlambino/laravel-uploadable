<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Http\UploadedFile;

trait Uploadable
{
    use Options, Relations, Validation;

    protected array $uploadFrom = [];

    public static function bootUploadable(): void
    {
        static::replacePreviousUploads(config('uploadable.replace_previous_uploads', false));
        static::validateUploads(config('uploadable.validate', true));
        static::uploadOnQueue(config('uploadable.upload_on_queue', null));
    }

    public function getUploadFilename(UploadedFile $file): string
    {
        return str_replace('.', '', microtime(true)).'-'.$file->hashName();
    }

    public function getUploadPath(UploadedFile $file): string
    {
        return $this->getTable().DIRECTORY_SEPARATOR.$this->{$this->getKeyName()};
    }

    public function getUploads(): array
    {
        if (! empty($this->uploadFrom)) {
            return $this->uploadFrom;
        }

        $rules = $this->getUploadRules();

        /** @var \Illuminate\Http\Request $request */
        $request = app('request');

        if ($this::$validateUploads) {
            $validatable = array_filter($rules, fn ($value) => ! empty($value));
            $request->validate($validatable, $this->uploadRulesMessages());
        }

        $fields = collect($rules)
            ->keys()
            ->zip($rules)
            ->map(fn ($pair) => is_numeric($pair[0]) ? $pair[1] : $pair[0])
            ->all();

        return $request->only($fields);
    }

    /**
     * Set the files that should be uploaded. This can be use on a Livewire
     * component where the request object doesn't contain the UploadedFiles.
     * Note that the validation will not be perform on these files, so make
     * sure that they are already validated. Livewire provides a way to do
     * the validation by using Validation attributes
     *
     * @param array|UploadedFile $files The files to upload.
     *                                  Can be a single or an array of files.
     *                                  File can be an instance of Illuminate\Http\UploadedFile
     *                                  or a full path to a file uploaded on the temporary disk.
     *
     * @return void
     */
    public function uploadFrom(array|UploadedFile|string $files): void
    {
        $this->uploadFrom = is_array($files) ? $files : [$files];
    }
}
