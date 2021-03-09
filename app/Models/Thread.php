<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;

class Thread extends Model
{
    use HasFactory;

    protected $fillable=[
        'thread_id',
        'subject',
        'record_time',
        'meta_data'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class,'thread_id','thread_id');
    }
}
