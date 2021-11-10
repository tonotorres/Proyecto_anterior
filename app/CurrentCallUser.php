<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallUser extends Model
{
    public $timestamps = false;

    protected $fillable = ['current_call_id', 'user_id', 'department_id', 'extension', 'start', 'administrative_time'];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function current_call()
    {
        return $this->belongsTo('App\CurrentCall');
    }
}
