<?php

namespace NadLambino\Uploadable\Dto;

use Laravel\SerializableClosure\SerializableClosure;

/**
 * @property bool $deleteModelOnUploadFail
 * @property bool $deleteModelOnQueueUploadFail
 * @property bool $forceDeleteUploads
 * @property bool $replacePreviousUploads
 * @property bool $rollbackModelOnUploadFail
 * @property bool $rollbackModelOnQueueUploadFail
 * @property string|null $queue
 * @property string $temporaryDisk
 */
class UploadOptions
{
    public function __construct(
        public readonly ?SerializableClosure $beforeSavingUploadUsing = null,
        public readonly bool $dontUpload = false,
        public readonly array $originalAttributes = [],
        private ?bool $deleteModelOnUploadFail = null,
        private ?bool $deleteModelOnQueueUploadFail = null,
        private ?bool $forceDeleteUploads = null,
        private ?bool $replacePreviousUploads = null,
        private ?bool $rollbackModelOnUploadFail = null,
        private ?bool $rollbackModelOnQueueUploadFail = null,
        private ?string $queue = null,
        private ?string $temporaryDisk = null,
    ) {
        $this->deleteModelOnUploadFail ??= config('uploadable.delete_model_on_upload_fail', true);
        $this->deleteModelOnQueueUploadFail ??= config('uploadable.delete_model_on_queue_upload_fail', false);
        $this->forceDeleteUploads ??= config('uploadable.force_delete_uploads', false);
        $this->replacePreviousUploads ??= config('uploadable.replace_previous_uploads', false);
        $this->rollbackModelOnUploadFail ??= config('uploadable.rollback_model_on_upload_fail', true);
        $this->rollbackModelOnQueueUploadFail ??= config('uploadable.rollback_model_on_queue_upload_fail', false);
        $this->queue ??= config('uploadable.upload_on_queue', null);
        $this->temporaryDisk ??= config('uploadable.temporary_disk', 'local');
    }

    /**
     * Make the properties read-only outside of this class
     * yet allow them to be set in the constructor.
     * This is to allow setting their default values
     * based on config values.
     *
     * @param  string  $name
     * @param  mixed  $value
     *
     * @throws \RuntimeException
     */
    public function __set($name, $value): void
    {
        throw new \RuntimeException(sprintf('Property %s is read-only', $name));
    }

    public function __get($name): mixed
    {
        return $this->$name;
    }
}
