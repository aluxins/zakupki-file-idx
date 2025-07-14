<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\FileRequest;
use Illuminate\Validation\ValidationException;
use Manticoresearch\Client;
use Exception;

class FilesController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function index(FileRequest $request, string $table): array
    {
        $client = $this->client();

        try {
            $table = $client->table($table);

            $search = $table
                ->search($request->validated()['search'])
                ->sort('id', 'desc')
                ->highlight(['content'], ['fragment_size' => 100])
                ->get();

            $documents = [];
            foreach ($search as $document) {
                $data = $document->getData();
                unset($data['content']);
                $documents[] = [
                    'id' => $document->getId(),
                    'data' => $data,
                    'snippets' => $document->getHighlight()
                ];
            }
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return $documents;
    }

    /**
     * @throws ValidationException
     */
    public function fileId(string $table, int $id): array
    {
        $client = $this->client();
        try {
            $table = $client->table($table);
            $document = $table->getDocumentById($id);
            if (empty($document)) {
                return ["Файл id = $id не найден."];
            }
            $data = $document->getData();
            $data['size'] = mb_strlen($data['content']);
            if (mb_strlen($data['content']) > 1024) {
                $data['content'] = mb_substr($data['content'], 0, 1024) . '...';
            }
            return $data;
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }
    }

    private function client(): Client
    {
        return new Client([
            'host' => config('manticoresearch.host'),
            'port' => config('manticoresearch.port'),
        ]);
    }
}
