<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TaskDescription extends Model
{
    protected $fillable = ['task_id', 'description'];
}
