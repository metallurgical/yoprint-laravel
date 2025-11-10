<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Upload;
use App\Models\Product;
use League\Csv\Reader;
use League\Csv\Statement;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CsvProcessedNotification;

class ProcessCsvUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uploadId;
    public $timeout = 1200;

    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    // public function handle()
    // {
    //     $upload = Upload::find($this->uploadId);
    //
    //     if (! $upload) return;
    //
    //     // Change status dari pending kepada processing
    //     $upload->update(['status' => 'processing']);
    //
    //     try {
    //         $path = $upload->path;
    //         $stream = Storage::readStream($path);
    //
    //         // Use League CSV for robust parsing
    //         $csv = Reader::createFromStream($stream);
    //         $csv->setHeaderOffset(0); // first row as header
    //
    //         $records = (new Statement())->process($csv);
    //
    //         // Count rows
    //         $rows = iterator_to_array($records);
    //         $total = count($rows);
    //         $upload->update(['total_rows' => $total]);
    //
    //         $batch = [];
    //         $batchSize = 500000;
    //         $processed = 0;
    //
    //         foreach ($rows as $row) {
    //             // Validate data terima dar Csv
    //             foreach ($row as $k => $v) {
    //                 if (is_string($v)) {
    //                     // remove BOM, trim, and re-encode to UTF-8 ignoring invalid bytes
    //                     $v = preg_replace('/\x{FEFF}/u', '', $v); // BOM
    //                     $v = trim($v);
    //                     $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8'); // drops invalid sequences
    //                 }
    //                 $row[$k] = $v;
    //             }
    //
    //             // Mapping data dari CSV
    //             $batch[] = [
    //                 'unique_key' => $row['UNIQUE_KEY'] ?? $row['unique_key'] ?? null,
    //                 'product_title' => $row['PRODUCT_TITLE'] ?? null,
    //                 'product_description' => $row['PRODUCT_DESCRIPTION'] ?? null,
    //                 'style' => $row['STYLE#'] ?? $row['STYLE'] ?? null,
    //                 'sanmar_mainframe_color' => $row['SANMAR_MAINFRAME_COLOR'] ?? null,
    //                 'size' => $row['SIZE'] ?? null,
    //                 'color_name' => $row['COLOR_NAME'] ?? null,
    //                 'piece_price' => is_numeric($row['PIECE_PRICE'] ?? null) ? (float) $row['PIECE_PRICE'] : null,
    //                 'updated_at' => now(),
    //                 'created_at' => now(),
    //             ];
    //
    //             $processed++;
    //
    //             if (count($batch) >= $batchSize) {
    //                 // upsert by unique_key
    //                 Product::upsert($batch,
    //                     ['unique_key'],
    //                     ['product_title','product_description','style','sanmar_mainframe_color','size','color_name','piece_price','updated_at']
    //                 );
    //                 $batch = [];
    //                 $upload->update(['processed_rows' => $processed]);
    //             }
    //         }
    //
    //         // remaining
    //         if (count($batch) > 0) {
    //             Product::upsert($batch,
    //                 ['unique_key'],
    //                 ['product_title','product_description','style','sanmar_mainframe_color','size','color_name','piece_price','updated_at']
    //             );
    //             $upload->update(['processed_rows' => $processed]);
    //         }
    //
    //         $upload->update(['status' => 'completed']);
    //     } catch (Throwable $e) {
    //         $upload->update([
    //             'status' => 'failed',
    //             'error' => $e->getMessage(),
    //         ]);
    //         throw $e; // optionally rethrow so the job is retried according to queue config
    //     }
    // }

    public function handle()
    {
        $upload = Upload::find($this->uploadId);

        if (! $upload) return;

        // Change status dari pending kepada processing
        $upload->update(['status' => 'processing']);

        try {
            $path = $upload->path;
            $stream = Storage::readStream($path);

            // Use League CSV for robust parsing
            $csv = Reader::createFromStream($stream);
            $csv->setHeaderOffset(0); // first row as header

            $records = (new Statement())->process($csv);

            // Count rows
            $rows = iterator_to_array($records);
            $total = count($rows);
            $upload->update(['total_rows' => $total]);

            $batch = [];
            $batchSize = 50500; // smaller batch for SQLite
            $processed = 0;

            foreach ($rows as $row) {
                // Clean each column value
                foreach ($row as $k => $v) {
                    if (is_string($v)) {
                        $v = preg_replace('/\x{FEFF}/u', '', $v); // BOM
                        $v = trim($v);
                        $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
                    }
                    $row[$k] = $v;
                }

                // Map CSV row to DB fields
                $batch[] = [
                    'unique_key' => $row['UNIQUE_KEY'] ?? $row['unique_key'] ?? null,
                    'product_title' => $row['PRODUCT_TITLE'] ?? null,
                    'product_description' => $row['PRODUCT_DESCRIPTION'] ?? null,
                    'style' => $row['STYLE#'] ?? $row['STYLE'] ?? null,
                    'sanmar_mainframe_color' => $row['SANMAR_MAINFRAME_COLOR'] ?? null,
                    'size' => $row['SIZE'] ?? null,
                    'color_name' => $row['COLOR_NAME'] ?? null,
                    'piece_price' => is_numeric($row['PIECE_PRICE'] ?? null) ? (float) $row['PIECE_PRICE'] : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                $processed++;

                if (count($batch) >= $batchSize) {
                    // Chunk the batch to avoid SQLite variable limit
                    foreach (array_chunk($batch, 25) as $smallChunk) {
                        Product::upsert(
                            $smallChunk,
                            ['unique_key'],
                            ['product_title','product_description','style','sanmar_mainframe_color','size','color_name','piece_price','updated_at']
                        );
                    }
                    $batch = [];
                    $upload->update(['processed_rows' => $processed]);
                }
            }

            // Remaining rows
            if (count($batch) > 0) {
                foreach (array_chunk($batch, 25) as $smallChunk) {
                    Product::upsert(
                        $smallChunk,
                        ['unique_key'],
                        ['product_title','product_description','style','sanmar_mainframe_color','size','color_name','piece_price','updated_at']
                    );
                }
                $upload->update(['processed_rows' => $processed]);
            }

            $upload->update(['status' => 'completed']);

            // Optionally notify user here about completion
            // Commented out to avoid sending email during tests, but here is the piece to do so:
            // Notification::route('mail', 'norlihazmey.ghazali@gmail.com')->notify(new CsvProcessedNotification($upload));

        } catch (Throwable $e) {
            $upload->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            throw $e; // rethrow so the job can be retried
        }
    }

}

