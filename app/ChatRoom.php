<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = ['company_id', 'message_type_id', 'account_id', 'name', 'from', 'to', 'is_active'];

    public function message_type() {
        return $this->belongsTo('App\MessageType');
    }

    public function users() {
        return $this->belongsToMany('App\User', 'user_chat_room')->withPivot( 'name', 'unread' );
    }

    public function departments() {
        return $this->belongsToMany('App\Department', 'department_chat_room');
    }

    public function accounts() {
        return $this->belongsToMany('App\Account', 'account_chat_room')->withPivot( 'name', 'unread' );
    }

    public function contacts() {
        return $this->belongsToMany('App\Contact', 'contact_chat_room')->withPivot( 'name', 'unread' );
    }

    public function last_messages() {
        return $this->hasMany('App\Message')->limit('10')->orderBy('id', 'desc');
    }
}
