<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\ImportReport;
use App\Models\ImportChunk;
use App\Models\User;
use App\Models\Image;
use Illuminate\Support\Facades\Log;

class CsvImportJob implements ShouldQueue
{

    use Dispatchable, Queueable;
    public $reportId;
    public $index;

    /**
     * Create a new job instance.
     */
    public function __construct($reportId, $index)
    {
        //
        $this->reportId = $reportId;
        $this->index = $index;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $report = ImportReport::findOrFail($this->reportId);

        // If checksum mismatch, STOP everything
        $currentChecksum = hash_file('sha256', storage_path("app/private/imports/{$report->file_name}"));
        if ($currentChecksum !== $report->checksum) {
            $report->update(['status' => 'failed']);
            return;
        }

        $chunk = ImportChunk::where('report_id', $this->reportId)
            ->where('chunk_index', $this->index)
            ->first();
        if ($chunk->processed) {
            return; // idempotency: chunk already processed
        }
        $handle = storage_path("app/private/imports/{$report->file_name}");
        $rows_clean = file($handle, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $firstRow =  str_getcsv($rows_clean[0]);
        $required = ['name', 'email', 'password', 'image_name'];

        if (count(array_intersect($firstRow, $required)) >= 1) {
            $total_rows = count($rows_clean) - 1;
            $header = $firstRow;
            $start = $this->index * 1000 + 1;
        } else {
            $total_rows = count($rows_clean);
            $header = $required;
            $start = $this->index * 1000;
        }
        $report->update(['total_rows' => $total_rows]);
        // Load chunk rows  
        $rows = array_slice(file($handle), $start, 1000);
        $seenKeys = []; //for tracking duplicates

        foreach ($rows as $line) {


            if (count($header) != count(str_getcsv($line))) {
                $line++;
                $report->increment('invalid');
                continue;
            }
            $data = array_combine($header, str_getcsv($line));

            $key = strtolower(trim($data['email']));
            // detect duplicates inside the CSV itself
            if (array_key_exists($key, $seenKeys)) {
                $report->increment('duplicates');
                continue;
            }

            $seenKeys[$key] = true;
            // Upsert
            $existing = User::where('email', $data['email'])->first();

            if ($data['image_name'] != '') {
                $imageName = basename($data['image_name']);
            }
            if ($existing) {


                $existing->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),

                ]);
                if ($data['image_name'] != '') {
                    $image = Image::updateOrCreate(
                        ['user_id' => $existing->id],
                        [
                            'original_name' => $imageName,
                            'user_id' => $existing->id,
                        ]
                    );
                }
                $report->increment('updated');
            } else {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                ]);
                if ($data['image_name'] != '') {
                    Image::create([
                        'original_name' => $imageName,
                        'user_id' => $user->id,
                    ]);
                }
                $report->increment('imported');
            }




            $chunk->update(['processed' => true]);
            // When all chunks done → complete
            $remaining = ImportChunk::where('report_id', $this->reportId)
                ->where('processed', false)
                ->count();

            if ($remaining == 0) {
                $report->update(['status' => 'completed']);
            }
        }
    }
}
