<?php

namespace App\Http\Services\FilesUpload;

use Illuminate\Support\Arr;

trait FileHelpers
{
    /**
     * Добавление поля в data.
     * @param int $id
     * @param string $k
     * @param string $v
     * @return void
     */
    private function addFieldForData(int $id, string $k, string $v): void
    {
        $this->data = Arr::map($this->data, function (array $value) use ($id, $k, $v) {
            if ($value['id'] === $id) {
                $value[$k] = $v;
            }
            return $value;
        });
    }

    /**
     * Парсинг uid файла из url строки, иначе sha1 строки.
     * @param int $id
     * @param string $url
     * @param string $reg_number
     * @return string
     */
    private function parseUid(int $id, string $url, string $reg_number): string
    {
        Arr::keyBy($this->data, 'id')[$id]['url'];
        $save = $reg_number . '_' . /*str_replace('uid=', '', parse_url($url, PHP_URL_QUERY)) ?: */sha1($url);
        $this->addFieldForData($id, 'save', $save);
        return $save;
    }

    /**
     * Парсинг оригинального имени файла.
     * @param string $s
     * @return string
     */
    private function parseFilename(string $s): string
    {
        preg_match('/filename="(.+)";*/', $s, $matches);
        $file_name = $matches[1] ?? '';
        // Имя файла может быть в кодировке ISO-8859-1 или URL-encoded.
        $file_name = @iconv("UTF-8", "ISO-8859-1", $file_name) ?: $file_name;
        return str_replace(' ', '_', urldecode($file_name));
    }
}
