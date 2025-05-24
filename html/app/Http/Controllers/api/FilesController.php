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
        $client = new Client([
            'host' => config('manticoresearch.host'),
            'port' => config('manticoresearch.port'),
        ]);

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
}
