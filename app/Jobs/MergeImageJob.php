<?php

namespace App\Jobs;

use App\Models\ImageUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use App\Jobs\GenerateVariantJob;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use App\Models\Image;

use Illuminate\Support\Facades\Log;

class MergeImageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Image $upload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $finalPath =  "chunks/{$this->upload->upload_id}_{$this->upload->original_name}";

        $out = fopen(Storage::path($finalPath), 'ab');

        for ($i = 0; $i < $this->upload->total_chunks; $i++) {
            fwrite($out, file_get_contents(
                Storage::path("chunks/{$this->upload->upload_id}/chunk_$i")
            ));
        }

        fclose($out);
        if (hash_file('sha256', Storage::path($finalPath)) !== $this->upload->checksum) {
            Storage::delete($finalPath);
            throw new \Exception('Checksum mismatch');
        }

        Storage::deleteDirectory("chunks/{$this->upload->upload_id}");

        $this->upload->update([
            'completed' => true,
            'final_path' => $finalPath
        ]);

        $uploadId = $this->upload->id;

        dispatch(new GenerateVariantJob($finalPath, $uploadId));
    }
}
