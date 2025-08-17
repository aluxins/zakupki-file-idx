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
     * @var int
     */
    public int $tries = 6;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 10*60, 60*60, 12*60*60, 24*60*60, 48*60*60];
    }

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
            $command = 'timeout ' . env('CONVERSION_TIMEOUT', '100') .'s nice pdftotext -nopgbrk '
                . Storage::disk('documents')->path('original/') . $file->save . ' '
                . Storage::disk('documents')->path('convert/') . $file->filename . '.txt';

            $return = shell_exec($command);
            Log::info($command . ' : ' . $return . ' ' . $file->save);
        }

        AddToSearch::dispatch($this->files)->onQueue('indexing')->delay(now()->addSeconds(10));
    }
}
