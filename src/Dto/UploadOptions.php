<?php

namespace NadLambino\Uploadable\Dto;

use Laravel\SerializableClosure\SerializableClosure;

readonly class UploadOptions
{
    public function __construct(
        public ?SerializableClosure $beforeSavingUploadUsing = null,
        public bool $deleteModelOnUploadFail = true,
        public bool $deleteModelOnQueueUploadFail = false,
        public bool $dontUpload = false,
        public bool $forceDeleteUploads = false,
        public array $originalAttributes = [],
        public bool $replacePreviousUploads = false,
        public bool $rollbackModelOnUploadFail = true,
        public bool $rollbackModelOnQueueUploadFail = false,
        public ?string $queue = null,
        public string $temporaryDisk = 'local',
    ) { }
}
