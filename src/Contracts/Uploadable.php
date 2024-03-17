<?php

namespace NadLambino\Uploadable\Contracts;

use Illuminate\Http\UploadedFile;

interface Uploadable
{
    public function upload(UploadedFile $file, ?string $path = null, ?string $name = null): ?string;

    public function get(string $file): ?string;

    public function url(string $file): ?string;

    public function temporaryUrl(string $file, int $expiration = 60, array $options = []): ?string;

    public function delete(string $file): bool;

    public function exists(string $file): bool;
}
