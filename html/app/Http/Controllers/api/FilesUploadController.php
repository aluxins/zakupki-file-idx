<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\FilesUploadRequest;
use App\Http\Services\FilesUpload\FilesUploadInterface;
use App\Jobs\Conversion;
use App\Jobs\ConversionPdf;
use Illuminate\Support\Facades\Log;

class FilesUploadController extends Controller
{
    public function index(FilesUploadRequest $request, FilesUploadInterface $upload): array
    {
        $download = $upload->download($request->validated());
        foreach ($download as $extension => $files) {
            $extension = strtolower($extension);
            $extension === 'pdf' ? $this->Pdf($files) : $this->Doc($files);
        }
        return $download;
    }

    private function Doc($files): void
    {
        Conversion::dispatch($files[0]->reg_number);
    }

    private function Pdf($files): void
    {
        foreach ($files as $file) {
            ConversionPdf::dispatch($file->save);
        }
    }
}
