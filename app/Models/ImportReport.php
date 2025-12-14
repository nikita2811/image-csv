<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportReport extends Model
{
    //
    protected $fillable = [
        'file_name',
        'checksum',
        'status',
        'total_rows',
        'imported',
        'updated',
        'invalid',
        'duplicates',

    ];
}
