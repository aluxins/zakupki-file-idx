<?php

use App\Http\Controllers\api\FilesController;
use App\Http\Controllers\api\FilesUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');*/

Route::post('/files-upload', [FilesUploadController::class, 'index']);
Route::get('/files/{table}', [FilesController::class, 'index'])->where('table', '[A-Za-z0-9-_]+');
Route::get('/files/{table}/id{id}', [FilesController::class, 'fileId'])->where('table', '[A-Za-z0-9-_]+')
    ->where('id', '[0-9]+');
