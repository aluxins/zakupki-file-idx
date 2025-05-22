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

class ConversionPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private string $name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $command = 'pdftotext -nopgbrk '
            . Storage::disk('documents')->path('original/') . $this->name . ' '
            . Storage::disk('documents')->path('convert/') . substr($this->name, 0, strripos($this->name, '.')) . '.txt';

        $return = shell_exec($command);
        Log::info($command .' : '. $return . ' ' . $this->name);
    }
}
