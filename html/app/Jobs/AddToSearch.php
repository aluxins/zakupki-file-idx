<?php

namespace App\Jobs;

use App\Http\Services\FilesUpload\FileDTO;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        $table = $client->table('files');
        $this->checkTable($client, $table);

        $documents_array = [];
        foreach ($this->files as $file) {
            $content = Storage::disk('documents')->get('convert/' . $file->filename . '.txt');
            if (!is_null($content) and mb_detect_encoding($content, ['ASCII', 'UTF-8'], true)) {
                $documents_array[$file->id] = [
                    'id' => $file->id,
                    'reg_number' => $file->reg_number,
                    // В случае индексации файлов из архива, конкатенируем их в один файл.
                    // Удаление пробелов, переносов и т.п., включая ZWNBSP (BOM).
                    'content' => ($documents_array[$file->id]['content'] ?? '') . trim($content, " \n\r\t\v\x00\xEF\xBB\xBF"),
                    'name' => $file->original,
                    'url' => $file->url,
                    'hash' => sha1($content),
                ];

            }
        }
        Log::info('Manticoresearch count indexed: ' . count($documents_array) . ' (' . count($this->files) . ')');

        if(!count($documents_array)){
            Log::info('Manticoresearch: not indexed');
            return;
        }

        try {
            $result = $table->addDocuments($documents_array);
            Log::info('Manticoresearch:' . json_encode($result));
        } catch (Exception $e) {
            Log::error('Manticoresearch Error:' . $e->getMessage() . '; Data: ' . serialize($this->files));
        }
        $this->unlink($this->files);

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
            $client->tables()->status(['table' => 'files']);
        } catch (Exception $e) {
            $table->create([
                'reg_number' => ['type' => 'string'],
                'content' => ['type' => 'text'],
                'name' => ['type' => 'text'],
                'url' => ['type' => 'string'],
                'hash' => ['type' => 'string'],
            ], [
                'min_stemming_len' => '1',
                'morphology' => 'stem_enru'
            ]);
        }
    }


}
