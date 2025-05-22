<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\FilesUploadRequest;
use App\Http\Services\FilesUpload\FilesUploadInterface;
use App\Http\Services\FilesUpload\FileUploadGuzzle;

class FilesUploadController extends Controller
{
    public function index(FilesUploadRequest $request, FilesUploadInterface $upload): array
    {
        return $upload->download($request->validated());
    }
}
