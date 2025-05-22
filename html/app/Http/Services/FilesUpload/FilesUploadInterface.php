<?php

namespace App\Http\Services\FilesUpload;

interface FilesUploadInterface
{
    public function download(array $data): array;
}
