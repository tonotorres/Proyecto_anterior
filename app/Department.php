<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['company_id', 'department_id', 'code', 'name', 'administrative_time'];

    public function users() {
        return $this->hasMany('App\User');
    }

    public function chat_rooms() {
        return $this->belongsToMany('App\ChatRoom', 'department_chat_room');
    }
}
