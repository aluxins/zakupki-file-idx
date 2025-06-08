<?php

namespace App\Http\Services\FilesUpload;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUploadHttp implements FilesUploadInterface
{
    use FileHelpers;
    private array $data;

    public function __construct()
    {
    }

    public function download(array $data): array
    {
        $this->data = $data;
        $this->withHttp();
        return Arr::map($this->data, function (array $item) {
            return new FileDTO($item);
        });
    }

    private function withHttp(): void
    {
        $responses = Http::pool(function (Pool $pool) {
            return Arr::mapWithKeys($this->data, function (array $item) use ($pool) {
                return [
                    $pool
                        ->as($item['id'])->withOptions([
                            //'sink' => Storage::disk('documents')->path('original/') . $this->parseUid($item['url']),
                        ])
                        ->get($item['url'])
                ];
            });
        });

        foreach ($responses as $key => $response) {
            if ($response instanceof \Exception) {
                Log::error('File upload error: ' . Arr::keyBy($this->data, 'id')[$key]['url']);
                continue;
            }
            if ($response->ok()) {
                $reg_number = Arr::keyBy($this->data, 'id')[$key]['reg_number'];
                $original = $this->parseFilename($response->getHeader('Content-disposition')[0]);
                $filename = $reg_number . '_' . sha1(Arr::keyBy($this->data, 'id')[$key]['url']);
                $extension = substr($original, strripos($original, '.')+1);
                $save = $filename . '.' . $extension;
                $this->addFieldForData($key, 'original', $original);
                $this->addFieldForData($key, 'filename', $filename);
                $this->addFieldForData($key, 'extension', strtolower($extension)/*mime_content_type($filename)['extension'] ?? ''*/);
                $this->addFieldForData($key, 'save', $save);
                if (Storage::disk('documents')->put(
                    'original/' . $save,
                    $response->getBody()
                )) {
                    $this->addFieldForData($key, 'status', true);
                }
            }
        }
    }

}
