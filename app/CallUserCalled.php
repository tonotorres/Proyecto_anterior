<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallUserCalled extends Model
{
    public $timestamps = false;

    protected $fillable = ['call_id', 'user_id', 'department_id', 'extension', 'start', 'answered'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
