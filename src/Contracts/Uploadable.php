<?php

namespace NadLambino\Uploadable\Contracts;

use Illuminate\Http\UploadedFile;

interface Uploadable
{
    public function upload(UploadedFile $file, ?string $path = null, ?string $name = null): ?string;
}
