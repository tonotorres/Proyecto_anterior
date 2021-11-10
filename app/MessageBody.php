<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MessageBody extends Model
{
    protected $fillable = ['message_body_type_id', 'message_id', 'content'];

    public function message_body_type() {
        return $this->belongsTo('App\MessageBodyType');
    }

    public function message() {
        return $this->belongsTo('App\Message');
    }
}
