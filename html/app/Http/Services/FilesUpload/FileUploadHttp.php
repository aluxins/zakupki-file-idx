<?php

namespace App\Http\Services\FilesUpload;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
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
        $data_dto = Arr::map($this->data, function (array $item) {
            return new FileDTO($item);
        });
        $collection = collect($data_dto);
        return $collection->groupBy('extension')->toArray();
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
            if ($response->ok()) {
                $reg_number = Arr::keyBy($this->data, 'id')[$key]['reg_number'];
                $filename = $this->parseFilename($response->getHeader('Content-disposition')[0]);
                if (Storage::disk('documents')->put(
                    'original/' . $reg_number . '_' . $filename,
                    $response->getBody()
                )) {
                    $this->addFieldForData($key, 'original', $filename);
                    $this->addFieldForData($key, 'save', $reg_number . '_' . $filename);
                    $this->addFieldForData($key, 'extension', pathinfo($filename)['extension'] ?? '');
                }
            }
        }
    }

}
