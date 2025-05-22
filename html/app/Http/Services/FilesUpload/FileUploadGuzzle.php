<?php

namespace App\Http\Services\FilesUpload;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class FileUploadGuzzle implements FilesUploadInterface
{
    use FileHelpers;
    private array $data;

    public function __construct()
    {
    }

    public function download(array $data): array
    {
        $this->data = $data;
        $this->withGuzzle();
        $data_dto = Arr::map($this->data, function (array $item) {
            return new FileDTO($item);
        });
        $collection = collect($data_dto);
        return $collection->groupBy('extension')->toArray();
    }

    private function withGuzzle(): void
    {
        $client = new Client();

        $promises = Arr::mapWithKeys($this->data, function (array $item) use ($client) {
            return [
                $item['id'] => $client->getAsync($item['url'], [
                    'sink' => Storage::disk('documents')->path('original/') . $this->parseUid(
                            $item['id'],
                            $item['url'],
                            $item['reg_number']
                        )
                ])
            ];
        });
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $key => $response) {
            if ($response['state'] === 'fulfilled') {
                $filename = $this->parseFilename($response['value']->getHeader('Content-disposition')[0]);
                $this->addFieldForData($key, 'original', $filename);
                $this->addFieldForData($key, 'extension', pathinfo($filename)['extension'] ?? '');
            }
        }
    }

}
