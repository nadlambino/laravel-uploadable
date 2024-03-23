<?php

namespace NadLambino\Uploadable;

readonly class UploadableConfig
{
    public function __construct(
        public readonly ?string $disk = null,
        public readonly ?string $path = null,
        public readonly ?string $host = null,
    ) { }

    public static function instance() : UploadableConfig
    {
        return app(UploadableConfig::class);
    }
}
