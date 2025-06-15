<?php

namespace App\Jobs;

use App\Http\Services\FilesUpload\FileDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConversionPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var FileDto[]
     */
    private array $files;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->files as $file) {
            $command = 'nice pdftotext -nopgbrk '
                . Storage::disk('documents')->path('original/') . $file->save . ' '
                . Storage::disk('documents')->path('convert/') . $file->filename . '.txt';

            $return = shell_exec($command);
            Log::info($command . ' : ' . $return . ' ' . $file->save);
        }

        AddToSearch::dispatch($this->files)->onQueue('indexing')->delay(now()->addSeconds(10));
    }
}
