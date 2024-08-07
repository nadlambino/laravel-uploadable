<?php

namespace NadLambino\Uploadable\Dto;

use Laravel\SerializableClosure\SerializableClosure;
use NadLambino\Uploadable\Actions\Upload;
use NadLambino\Uploadable\Models\Upload as ModelsUpload;

/**
 * @property-read bool $replacePreviousUploads
 * @property-read string $uploadModelClass
 * @property-read ?string $uploadOnQueue
 */
class UploadOptions
{
    public readonly bool $deleteModelOnUploadFail;

    public readonly bool $deleteModelOnQueueUploadFail;

    public readonly bool $forceDeleteUploads;

    public readonly bool $rollbackModelOnUploadFail;

    public readonly bool $rollbackModelOnQueueUploadFail;

    public readonly string $temporaryDisk;

    public readonly array $disabledModels;

    public readonly array $onlyModels;

    public function __construct(
        public readonly ?SerializableClosure $beforeSavingUploadUsing = null,
        public readonly bool $disableUpload = false,
        public readonly array $originalAttributes = [],
        public readonly ?array $uploadStorageOptions = null,
        public readonly ?string $disk = null,
        public readonly array $uploadAttributes = [],
        private ?string $uploadModelClass = null,
        private ?bool $replacePreviousUploads = null,
        private ?string $uploadOnQueue = null
    ) {
        $this->deleteModelOnUploadFail = config('uploadable.delete_model_on_upload_fail', true);
        $this->deleteModelOnQueueUploadFail = config('uploadable.delete_model_on_queue_upload_fail', false);
        $this->disabledModels = Upload::$disabledModels;
        $this->forceDeleteUploads = config('uploadable.force_delete_uploads', false);
        $this->onlyModels = Upload::$onlyModels;
        $this->replacePreviousUploads ??= config('uploadable.replace_previous_uploads', false);
        $this->rollbackModelOnUploadFail = config('uploadable.rollback_model_on_upload_fail', true);
        $this->rollbackModelOnQueueUploadFail = config('uploadable.rollback_model_on_queue_upload_fail', false);
        $this->uploadModelClass ??= config('uploadable.uploads_model', ModelsUpload::class);
        $this->uploadOnQueue ??= config('uploadable.upload_on_queue', null);
        $this->temporaryDisk = config('uploadable.temporary_disk', 'local');
    }

    /**
     * This is to emulate a read-only property.
     *
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        throw new \Exception(sprintf('Cannot set read-only property %s', $name));
    }

    public function __get($name)
    {
        return $this->$name;
    }
}
