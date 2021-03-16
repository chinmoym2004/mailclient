<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable=[
        'attachment_id',
        'filename',
        'mimeType',
        'data',
        'file_path',
        'record_time'
    ];
    
}
