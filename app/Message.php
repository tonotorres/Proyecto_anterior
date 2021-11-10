<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['chat_room_id', 'message_type_id', 'write_user_id', 'read_user_id', 'account_id', 'contact_id', 'from', 'to', 'fromName', 'toName', 'subject', 'read_at'];

    public function chat_room() {
        return $this->belongsTo('App\ChatRoom');
    }

    public function message_type() {
        return $this->belongsTo('App\MessageType');
    }

    public function write_user() {
        return $this->belongsTo('App\User', 'write_user_id');
    }

    public function read_user() {
        return $this->belongsTo('App\User', 'read_user_id');
    }

    public function account() {
        return $this->belongsTo('App\Account');
    }

    public function contact() {
        return $this->belongsTo('App\Contact');
    }

    public function message_body() {
        return $this->hasOne('App\MessageBody');
    }
}
