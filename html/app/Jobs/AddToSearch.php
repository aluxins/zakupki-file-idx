<?php

namespace App\Jobs;

use App\Http\Services\FilesUpload\FileDTO;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Manticoresearch\Client;

class AddToSearch implements ShouldQueue
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
    public function handle(): void
    {
        $client = new Client([
            'host' => config('manticoresearch.host'),
            'port' => config('manticoresearch.port'),
        ]);
        $table = $client->table('documents');
        $this->checkTable($client, $table);

        $documents = Arr::map($this->files, function (FileDTO $value) {
            $content = Storage::disk('documents')->get('convert/' . $value->filename . '.txt');
            return $content
                ? [
                    'id' => $value->id,
                    'reg_number' => $value->reg_number,
                    'content' => Storage::disk('documents')->get('convert/' . $value->filename . '.txt'),
                    'name' => $value->original,
                    'url' => $value->url,
                    'hash' => sha1($content),
                ]
                : [];
        });

        $result = $table->addDocuments($documents);

        Log::info('Manticoresearch:' . json_encode($result));

    }

    private function checkTable($client, $table): void
    {
        try {
            $client->tables()->status(['table' => 'documents']);
        } catch (Exception $e) {
            $table->create([
                'reg_number' => ['type' => 'text'],
                'content' => ['type' => 'text'],
                'name' => ['type' => 'text'],
                'url' => ['type' => 'text'],
                'hash' => ['type' => 'text'],
            ], [
                'min_stemming_len' => '1',
                'morphology' => 'stem_enru'
            ]);
        }
    }
}
