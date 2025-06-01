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

        $documents = Arr::map($this->files, function (FileDTO $file) {
            $content = Storage::disk('documents')->get('convert/' . $file->filename . '.txt');
            return [
                    'id' => $file->id,
                    'reg_number' => $file->reg_number,
                    'content' => trim($content) ?: 'Файл не найден в хранилище.',
                    'name' => $file->original,
                    'url' => $file->url,
                    'hash' => sha1($content),
                ];
        });

        try {
            $result = $table->addDocuments($documents);
            Log::info('Manticoresearch:' . json_encode($result));
            $this->unlink($this->files);
        } catch (Exception $e) {
            Log::error('Manticoresearch Error:' . $e->getMessage() . '; Data: ' . serialize($this->files));
        }

    }

    /**
     * Удаление файлов.
     * @param $files
     * @return void
     */
    private function unlink($files): void
    {
        foreach ($files as $file) {
            try {
                unlink(Storage::disk('documents')->path('original/' . $file->save));
                unlink(Storage::disk('documents')->path('convert/' . $file->filename . '.txt'));
            } catch (Exception $e) {
                Log::error('Ошибка удаления файлов: ' . $e->getMessage() . '; Data: ' . serialize($file));
            }
        }
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
