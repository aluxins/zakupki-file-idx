<?php

namespace App\Http\Services\FilesUpload;

class FileDTO
{
    public int $id;
    public string $url;
    public string $reg_number;
    public string $original;
    public string $save;
    public string $extension;

    public function __construct($data)
    {
        foreach ($data as $key => $value){
            $this->$key = $value;
        }
    }
}
