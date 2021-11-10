<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $fillable = ['company_id', 'department_id', 'number', 'name'];

    public function department() {
        return $this->belongsTo('App\Extension');
    }

    public function paused_users() {
        return $this->belongsToMany('App\User', 'queue_user_paused');
    }
}
