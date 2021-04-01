<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;
use App\Models\EmailTracker;

class Thread extends Model
{
    use HasFactory;

    protected $fillable=[
        'thread_id',
        'subject',
        'record_time',
        'meta_data',
        'has_sent',
        'is_inbox'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class,'thread_id','thread_id');
    }

    public function user()
    {
        return $this->belongsTo(EmailTracker::class,'email_tracker_id','id');
    }
}
