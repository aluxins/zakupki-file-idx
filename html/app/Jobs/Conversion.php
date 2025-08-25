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

/**
 * Пакетная конвертация файлов DOC и XLS с помощью libreoffice.
 *
 * Используется пакет soffice с параметром --convert-to OutputFileExtension[:OutputFilterName].
 *
 * Подробное описание параметров OutputFileExtension[:OutputFilterName]:
 * ```
 * https://wiki.openoffice.org/wiki/Documentation/DevGuide/Spreadsheets/Filter_Options
 * https://help.libreoffice.org/7.4/en-US/text/shared/guide/convertfilters.html
 * ```
 */
class Conversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var FileDto[]
     */
    private array $files;

    private string $prefixArchive;

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
    public function __construct(array $files, string $prefixArchive = '')
    {
        $this->files = $files;
        $this->prefixArchive = $prefixArchive;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $originalFile = glob(Storage::disk('documents')->path('original/') . $this->prefixArchive . $this->files[0]->reg_number . '_*', \GLOB_BRACE);

        $command_doc = $this->execDoc();
        $command_xls = $this->execXls();
        $return_doc = shell_exec($command_doc);
        $return_xls = shell_exec($command_xls);

        $convertFile = glob(Storage::disk('documents')->path('convert/') . $this->prefixArchive . $this->files[0]->reg_number . '_*', \GLOB_BRACE);

        $listFile = [
            'Original' => ($originalFile ? implode(', ', $originalFile) : 'not found'),
            'Convert' => ($convertFile ? implode(', ', $convertFile) : 'not found')
        ];

        $this->Logger($listFile, $command_doc, $return_doc, $command_xls, $return_xls);

        AddToSearch::dispatch($this->files)->onQueue('indexing')->delay(now()->addSeconds(10));
    }

    /**
     * Тип файлов: doc, docx, odt
     *
     * EXEC:
     * ```
     *  timeout 60s nice soffice --headless
     *  --convert-to "txt:Text (encoded):UTF8"
     *  --outdir /usr/share/nginx/html/storage/app/documents/convert/
     *  /usr/share/nginx/html/storage/app/documents/original/0372200014225000302_*.[do][do][ct]*
     * ```
     */
    private function execDoc(): string
    {
        return 'timeout ' . env('CONVERSION_TIMEOUT', '100') .'s nice soffice --headless --convert-to "txt:Text (encoded):UTF8" --outdir '
            . Storage::disk('documents')->path('convert/') . ' '
            . Storage::disk('documents')->path('original/') . $this->prefixArchive . $this->files[0]->reg_number . '_*.[do][do][ct]*';

    }

    /**
     * Тип файлов: xls, xlsx, ods
     *
     * EXEC:
     * ```
     *  timeout 60s nice soffice --headless
     *  --convert-to "txt:Text - txt - csv (StarCalc):9,34,76,1"
     *  --outdir /usr/share/nginx/html/storage/app/documents/convert/
     *  /usr/share/nginx/html/storage/app/documents/original/0372200014225000302_*.xls*
     * ```
     */
    private function execXls(): string
    {
        return 'timeout ' . env('CONVERSION_TIMEOUT', '100') .'s nice soffice --headless --convert-to "txt:Text - txt - csv (StarCalc):9,34,76,1" --outdir '
            . Storage::disk('documents')->path('convert/') . ' '
            . Storage::disk('documents')->path('original/') . $this->prefixArchive . $this->files[0]->reg_number . '_*.[xo][ld][s]*';

    }

    /**
     * Логирование результатов конвертации файлов.
     * @param array $listFile
     * @param string $command_doc
     * @param ?string $return_doc
     * @param string $command_xls
     * @param ?string $return_xls
     * @return void
     */
    private function Logger(
        array $listFile,
        string $command_doc,
        ?string $return_doc,
        string $command_xls,
        ?string $return_xls
    ): void {
        Log::info('Process Conversion DOC and XLS files', [
            'listFile' => $listFile,
            'doc' => [
                'command' => $command_doc,
                'return' => $return_doc
            ],
            'xls' => [
                'command' => $command_xls,
                'return' => $return_xls
            ],
        ]);
    }
}
