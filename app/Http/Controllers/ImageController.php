<?php

namespace App\Http\Controllers;

use App\Models\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\MergeImageJob;
use Illuminate\Support\Facades\Log;
use App\Models\Image;

class ImageController extends Controller
{
    //




    public function chunk(Request $request)
    {

        $request->validate([
            'file' => 'required|file',
            'upload_id' => 'required|uuid',
            'chunk_index' => 'required|integer',
            'total_chunks' => 'required|integer',
            'checksum' => 'required|string',
            'original_name' => 'required|string'
        ]);
        $upload = Image::where('original_name', $request->original_name)->first();
        if (!$upload) {
            return response()->json(['status' => 'invalid Image']);
        }
        $upload->update([
            'upload_id' => $request->upload_id,
            'total_chunks' => $request->total_chunks,
            'checksum' => $request->checksum,
            'uploaded_chunks' => []
        ]);

        Log::info($upload);
        $upload->uploaded_chunks = $upload->uploaded_chunks ?? [];
        if (in_array($request->chunk_index, $upload->uploaded_chunks)) {
            return response()->json(['status' => 'skipped']);
        }
        $dir = "chunks/{$upload->upload_id}";
        Storage::makeDirectory($dir);

        $request->file('file')
            ->storeAs($dir, "chunk_{$request->chunk_index}");

        $chunks = collect($upload->uploaded_chunks)
            ->push($request->chunk_index)
            ->unique()
            ->sort()
            ->values();
        $upload->update(['uploaded_chunks' => $chunks]);


        dispatch(new MergeImageJob($upload));


        return response()->json(['status' => 'uploaded']);
    }
}
