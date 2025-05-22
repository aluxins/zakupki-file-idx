<?php

namespace App\Providers;

use App\Http\Services\FilesUpload\FilesUploadInterface;
use App\Http\Services\FilesUpload\FileUploadGuzzle;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FilesUploadInterface::class, FileUploadGuzzle::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
