<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallUser extends Model
{
    public $timestamps = false;

    protected $fillable = ['call_id', 'user_id', 'department_id', 'extension', 'start', 'administrative_time'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
