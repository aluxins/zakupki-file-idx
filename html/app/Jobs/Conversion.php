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

class Conversion implements ShouldQueue
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
        //libreoffice --infilter="writer_pdf_import" --convert-to docx input.pdf
        $command = 'nice soffice --headless --convert-to txt --outdir '
            . Storage::disk('documents')->path('convert/') . ' '
            . Storage::disk('documents')->path('original/') . $this->files[0]->reg_number . '_*';

        $return = shell_exec($command);
        Log::info($command .' : '. $return . ' ' . $this->files[0]->reg_number);

        AddToSearch::dispatch($this->files)->onQueue('indexing')->delay(now()->addSeconds(10));
    }
}
