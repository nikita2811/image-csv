<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImportReport;
use App\Models\ImportChunk;
use App\Jobs\CsvImportJob;
use Illuminate\Support\Facades\Log;

class UserImportController extends Controller
{
    //
    public function csvUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);
        $file = $request->file('file');

        $storagePath = $file->storeAs('imports', $file->getClientOriginalName());
        $checksum = hash_file('sha256', storage_path("app/private/imports/{$file->getClientOriginalName()}"));
        $report = ImportReport::create([
            'file_name' => $file->getClientOriginalName(),
            'status' => 'pending',
            'checksum' => $checksum,
        ]);
        // Break CSV into chunks
        $chunkSize = 1000;
        $index = 0;
        $handle = fopen($file, 'r');
        $chunk = [];
        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = $row;

            if (count($chunk) === $chunkSize) {
                ImportChunk::create([
                    'report_id'  => $report->id,
                    'chunk_index' => $index
                ]);

                CsvImportJob::dispatch($report->id, $index, $chunk);

                $index++;
                $chunk = [];
            }
        }
        // Dispatch remaining rows
        if (!empty($chunk)) {
            ImportChunk::create([
                'report_id'  => $report->id,
                'chunk_index' => $index
            ]);

            CsvImportJob::dispatch($report->id, $index, $chunk);
        }

        fclose($handle);

        // Update status once (not inside loop)
        $report->update(['status' => 'processing']);

        return response()->json([
            'report_id' => $report->id,
            'chunks'    => $index + 1
        ]);
    }
}
