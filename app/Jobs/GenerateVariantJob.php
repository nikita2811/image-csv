<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Models\ImageUpload;
use App\Models\ImageVariant;

use Intervention\Image\Drivers\Gd\Driver;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;

class GenerateVariantJob implements ShouldQueue
{
    use Dispatchable, Queueable;
    public $finalPath;
    public $uploadId;

    /**
     * Create a new job instance.
     */
    public function __construct($finalPath, $uploadId)
    {
        //
        $this->finalPath = $finalPath;
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {


        $manager = new ImageManager(new Driver());

        $image = $manager->read(Storage::path($this->finalPath));
        foreach ([256, 512, 1024] as $size) {
            $clone = clone $image;

            $clone->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $variantPath = "variants/{$size}_" . basename($this->finalPath);

            Storage::put(
                $variantPath,
                (string) $clone->encode()
            );
            ImageVariant::create([
                'path' => $variantPath,
                'image_id' => $this->uploadId
            ]);
        }
    }
}
