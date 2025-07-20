<?php

namespace App\Jobs;

use App\Http\Services\FilesUpload\FileDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExtractArchive implements ShouldQueue
{
    use Queueable;

    /**
     * @var FileDto[]
     */
    private array $files;

    /**
     * Create a new job instance.
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        foreach ($this->files as $file) {
            $files_extract = [];
            if ($file->extension === 'zip') {
                $files_extract = $this->unzip($file);
            }
            if ($file->extension === 'rar') {
                $files_extract = $this->unrar($file);
            }

            unlink(Storage::disk('documents')->path('original/' . $file->save));
            if(count($files_extract))
            {
                $groups = $this->groupFiles($files_extract);
                // Тут костыль. Если в архиве одновременно pdf и doc, то в индекс уйдут только pdf.
                if (count($groups['pdf'])) {
                    $this->sendJobPdf($groups['pdf']);
                } elseif (count($groups['doc'])) {
                    $this->sendJobDoc($groups['doc']);
                }

                Log::info('Files have been extracted ' . $file->save .': ' . json_encode($files_extract));
                continue;
            }
            Log::info('Error extracting files ' . $file->save);
        }
    }

    private function groupFiles(array $files): array
    {
        $groups = [
            'pdf' => [],
            'doc' => [],
        ];
        foreach ($files as $file) {
            if(!$file->status){
                continue;
            }
            if ($file->extension === 'pdf') {
                $groups['pdf'][] = $file;
                continue;
            }
            if ($file->extension === 'zip' or $file->extension === 'rar') {
                continue;
            }
            $groups['doc'][] = $file;
        }
        return $groups;
    }

    /**
     * @param array $files
     * @return void
     */
    private function sendJobDoc(array $files): void
    {
        Conversion::dispatch($files, 'archive_')->onQueue('conversionDoc');
    }

    /**
     * @param array $files
     * @return void
     */
    private function sendJobPdf(array $files): void
    {
        ConversionPdf::dispatch($files)->onQueue('conversionPdf');
    }

    private function unzip(FileDTO $file): array
    {
        $files_extract = [];
        $path = Storage::disk('documents')->path('original/') . $file->save;
        $file_prefix_extract = 'archive_' . $file->reg_number . '_' . sha1($file->url) . '_';
        $zip = new ZipArchive;
        if ($zip->open($path) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileInfo = pathinfo($filename);
                $files_extract[$i] = new FileDTO([
                    'id' => $file->id,
                    'url' => $file->url,
                    'reg_number' => $file->reg_number,
                    'original' => $file->original,
                    'save' => $file_prefix_extract . sha1($fileInfo['basename']) . '.' . $fileInfo['extension'],
                    'extension' => $fileInfo['extension'],
                    'filename' => $file_prefix_extract . sha1($fileInfo['basename']),
                    'status' => true,
                ]);
                copy('zip://' . $path . '#' . $filename, Storage::disk('documents')->path('original/') . $files_extract[$i]->save);
            }
            $zip->close();
        } else {
            Log::info('Ошибка извлечения архива ' . $file->save);
        }
        return $files_extract;
    }

    private function unrar(FileDTO $file): array
    {
        return [];
    }


}
