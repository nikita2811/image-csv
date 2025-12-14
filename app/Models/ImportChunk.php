<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportChunk extends Model
{
    //
    protected $fillable = [
        'report_id',
        'chunk_index',
        'processed',
    ];
}
