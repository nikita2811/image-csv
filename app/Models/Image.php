<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //
    protected $fillable = [
        'file',
        'user_id',
        'upload_id',
        'chunk_index',
        'total_chunks',
        'checksum',
        'original_name',
        'uploaded_chunks',
        'final_path',

    ];
    protected $casts = [
        'uploaded_chunks' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function variants()
    {
        return $this->hasMany(ImageVariant::class);
    }
}
