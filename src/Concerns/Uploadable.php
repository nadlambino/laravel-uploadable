<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Http\UploadedFile;

trait Uploadable
{
    use Options, Relations, Validation;

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

    public function getUploads() : array
    {
        $rules = $this->getUploadRules();

        /** @var \Illuminate\Http\Request $request */
        $request = app('request');

        if ($this::$validateUploads) {
            $validatable = array_filter($rules, fn($value) => ! empty($value));
            $request->validate($validatable, $this->uploadRulesMessages());
        }

        $fields = collect($rules)
            ->keys()
            ->zip($rules)
            ->map(fn($pair) => is_numeric($pair[0]) ? $pair[1] : $pair[0])
            ->all();

        return $request->only($fields);
    }
}
