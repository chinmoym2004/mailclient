<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attachment;

class Message extends Model
{
    use HasFactory;

    protected $fillable=[
        'thread_id',
        'message_id',
        'body',
        'from',
        'to',
        'cc',
        'bcc',
        'record_time',
        'meta_data'
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class,'message_id','message_id');
    }
}
