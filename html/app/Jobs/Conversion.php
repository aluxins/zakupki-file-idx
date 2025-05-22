<?php

namespace App\Jobs;

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
     * @var string
     */
    private string $hash;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //libreoffice --infilter="writer_pdf_import" --convert-to docx input.pdf
        $command = 'soffice --headless --convert-to txt --outdir '
            . Storage::disk('documents')->path('convert/') . ' '
            . Storage::disk('documents')->path('original/') . $this->hash . '_*';

        $return = shell_exec($command);
        Log::info($command .' : '. $return . ' ' . $this->hash);

    }
}
