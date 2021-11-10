<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskTime extends Model
{
    protected $fillable = ['task_id', 'user_id', 'start', 'duration'];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function task_time_description() {
        return $this->hasOne('App\TaskTimeDescription');
    }
}