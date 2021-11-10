<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'comment'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
