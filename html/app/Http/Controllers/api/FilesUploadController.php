<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\FilesUploadRequest;
use App\Http\Services\FilesUpload\FilesUploadInterface;
use App\Jobs\Conversion;
use App\Jobs\ConversionPdf;
use App\Jobs\ExtractArchive;

class FilesUploadController extends Controller
{
    public function index(FilesUploadRequest $request, FilesUploadInterface $upload): array
    {
        $files = $upload->download($request->validated());
        $pdf = [];
        $doc = [];
        $zip_rar = [];
        // Группировка файлов по типу.
        foreach ($files as $file) {
            if(!$file->status){
                continue;
            }
            if ($file->extension === 'pdf') {
                $pdf[] = $file;
                continue;
            }
            if ($file->extension === 'zip' or $file->extension === 'rar') {
                $zip_rar[] = $file;
                continue;
            }
            $doc[] = $file;
        }
        if (count($pdf)) {
            $this->sendJobPdf($pdf);
        }
        if (count($doc)) {
            $this->sendJobDoc($doc);
        }
        if (count($zip_rar)) {
            $this->sendJobArchive($zip_rar);
        }

        return $files;
    }

    /**
     * @param array $files
     * @return void
     */
    private function sendJobDoc(array $files): void
    {
        Conversion::dispatch($files)->onQueue('conversionDoc');
    }

    /**
     * @param array $files
     * @return void
     */
    private function sendJobPdf(array $files): void
    {
        ConversionPdf::dispatch($files)->onQueue('conversionPdf');
    }

    private function sendJobArchive(array $zip_rar): void
    {
        ExtractArchive::dispatch($zip_rar)->onQueue('extractArchive');
    }
}
